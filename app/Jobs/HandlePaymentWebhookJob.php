<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\Payable;
use App\Enums\PaymentStatusEnum;
use App\Models\Payment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

#[Tries(3)]
#[Backoff(10)]
class HandlePaymentWebhookJob implements ShouldQueue
{
    use Queueable;

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
        $orderReference = Arr::get($this->data, 'orderReference');

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

        $newStatus = PaymentStatusEnum::fromWayForPay(Arr::get($this->data, 'transactionStatus', ''));

        $payment->fill([
            'transaction_status' => $newStatus,
            'reason'             => Arr::get($this->data, 'reason'),
            'reason_code'        => Arr::has($this->data, 'reasonCode') ? (string) Arr::get($this->data, 'reasonCode') : null,
            'payment_system'     => Arr::get($this->data, 'paymentSystem'),
            'card_type'          => Arr::get($this->data, 'cardType'),
            'issue_bank_name'    => Arr::get($this->data, 'issuerBankName'),
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
