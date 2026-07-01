<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Contracts\Payable;
use App\Enums\PaymentStatusEnum;
use App\Enums\WayForPayResponseStatusEnum;
use App\Models\Payment;
use App\Support\CurrencyConverter;
use AratKruglik\WayForPay\Facades\WayForPay;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class RefundPaymentAction
{
    use AsAction;

    public function handle(Payment $payment, string $comment = 'Admin refund'): bool
    {
        $currentStatus = $payment->transaction_status;
        if ($currentStatus !== PaymentStatusEnum::APPROVED) {
            return false;
        }

        $amountFloat = CurrencyConverter::fromCents($payment->amount);
        $currency = $payment->currency;

        $response = WayForPay::refund(
            $payment->order_reference,
            $amountFloat,
            $currency,
            $comment,
        );

        $transactionStatus = PaymentStatusEnum::fromWayForPay(Arr::get($response, 'transactionStatus', ''));
        $responseStatus = WayForPayResponseStatusEnum::tryFrom(Arr::get($response, 'status', ''));

        if ($transactionStatus !== PaymentStatusEnum::REFUNDED && $responseStatus !== WayForPayResponseStatusEnum::SUCCESS) {
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
