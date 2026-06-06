<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Contracts\Payable;
use App\Enums\PaymentStatusEnum;
use App\Models\Payment;
use App\Support\CurrencyConverter;
use AratKruglik\WayForPay\Facades\WayForPay;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class RefundPaymentAction
{
    use AsAction;

    public function handle(Payment $payment, string $comment = 'Admin refund'): bool
    {
        $currentStatus = PaymentStatusEnum::tryFrom((string) $payment->transaction_status);
        if ($currentStatus !== PaymentStatusEnum::APPROVED) {
            return false;
        }

        $amountFloat = CurrencyConverter::fromKopiyky($payment->amount);
        $currency = $payment->currency;

        $response = WayForPay::refund(
            $payment->order_reference,
            $amountFloat,
            $currency,
            $comment,
        );

        if (($response['transactionStatus'] ?? '') !== 'Refunded' && ($response['status'] ?? '') !== 'success') {
            return false;
        }

        DB::transaction(function () use ($payment): void {
            $payment->fill([
                'transaction_status' => PaymentStatusEnum::REFUNDED,
                'refunded_at'        => now(),
                'refund_amount'      => $payment->amount,
            ]);
            $payment->save();

            $payable = $payment->payable;
            if ($payable instanceof Payable) {
                $payable->markUnpaid();
            }
        });

        return true;
    }
}
