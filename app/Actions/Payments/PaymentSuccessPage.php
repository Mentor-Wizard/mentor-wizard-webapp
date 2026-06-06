<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Enums\PaymentStatusEnum;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;

class PaymentSuccessPage
{
    use AsController;

    public function handle(Request $request): Response
    {
        $orderReference = $request->query('orderReference');

        $payment = Payment::query()
            ->where('order_reference', $orderReference)
            ->with('payable')
            ->first();

        return Inertia::render('Payments/Success', [
            'payment' => $payment ? [
                'order_reference'    => $payment->order_reference,
                'amount'             => $payment->amount,
                'currency'           => $payment->currency,
                'transaction_status' => PaymentStatusEnum::tryFrom((string) $payment->transaction_status)?->value,
                'payment_system'     => $payment->payment_system,
                'created_at'         => $payment->created_at?->toIso8601String(),
            ] : null,
            'payable' => $payment?->payable ? [
                'type'  => class_basename($payment->payable_type),
                'id'    => $payment->payable->getKey(),
                'label' => method_exists($payment->payable, 'getPayableLabel')
                    ? $payment->payable->getPayableLabel()
                    : '',
            ] : null,
        ]);
    }
}
