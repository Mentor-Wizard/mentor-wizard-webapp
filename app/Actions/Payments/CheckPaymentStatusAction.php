<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Enums\PaymentStatusEnum;
use App\Models\Payment;
use AratKruglik\WayForPay\Facades\WayForPay;
use Lorisleiva\Actions\Concerns\AsAction;

class CheckPaymentStatusAction
{
    use AsAction;

    public function handle(Payment $payment): PaymentStatusEnum
    {
        $response = WayForPay::checkStatus($payment->order_reference);

        $status = PaymentStatusEnum::fromWayForPay($response['transactionStatus'] ?? '');

        $currentStatus = $payment->transaction_status;
        if ($currentStatus !== $status) {
            $payment->fill(['transaction_status' => $status]);
            $payment->save();
        }

        return $status;
    }
}
