<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\Payable;
use App\Enums\PaymentStatusEnum;
use App\Models\Payment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HandlePaymentWebhookJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 10;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly array $data,
    ) {
        $this->onQueue('payments');
    }

    public function handle(): void
    {
        $orderReference = $this->data['orderReference'] ?? null;

        if ($orderReference === null) {
            Log::warning('WayForPay webhook missing orderReference', [
                'keys' => array_keys($this->data),
            ]);

            return;
        }

        DB::transaction(function () use ($orderReference): void {
            $this->processWebhook($orderReference);
        });
    }

    private function processWebhook(string $orderReference): void
    {
        /** @var Payment|null $payment */
        $payment = Payment::query()
            ->where('order_reference', $orderReference)
            ->lockForUpdate()
            ->first();

        if ($payment === null) {
            Log::warning('WayForPay webhook: payment not found', ['order_reference' => $orderReference]);

            return;
        }

        $currentStatus = $payment->transaction_status;
        if ($currentStatus?->isTerminal()) {
            return;
        }

        $newStatus = PaymentStatusEnum::fromWayForPay($this->data['transactionStatus'] ?? '');

        $payment->fill([
            'transaction_status' => $newStatus,
            'reason'             => $this->data['reason'] ?? null,
            'reason_code'        => isset($this->data['reasonCode']) ? (string) $this->data['reasonCode'] : null,
            'payment_system'     => $this->data['paymentSystem'] ?? null,
            'card_type'          => $this->data['cardType'] ?? null,
            'issue_bank_name'    => $this->data['issuerBankName'] ?? null,
        ]);

        $payment->save();

        $this->syncPayableStatus($payment, $newStatus);
    }

    private function syncPayableStatus(Payment $payment, PaymentStatusEnum $newStatus): void
    {
        $payable = $payment->payable;

        if (! ($payable instanceof Payable)) {
            return;
        }

        if ($newStatus === PaymentStatusEnum::APPROVED) {
            $payable->markPaid();
        } elseif ($newStatus === PaymentStatusEnum::DECLINED) {
            $payable->markUnpaid();
        }
    }
}
