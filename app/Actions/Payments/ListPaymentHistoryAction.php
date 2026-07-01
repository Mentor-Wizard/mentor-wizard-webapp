<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Enums\PaymentStatusEnum;
use App\Http\Requests\Payments\ListPaymentHistoryRequest;
use App\Models\MentorSession;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;

class ListPaymentHistoryAction
{
    use AsController;

    public function handle(ListPaymentHistoryRequest $request): Response
    {
        $user = $request->user();

        $payments = Payment::query()
            ->with('payable')
            ->whereHasMorph(
                'payable',
                [MentorSession::class],
                fn (Builder $q) => $q->where('menti_id', $user->getKey())
                    ->orWhere('mentor_id', $user->getKey()),
            )
            ->tap(fn (Builder $q) => $this->applyFilters($q, $request))->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Payments/History', [
            'payments' => [
                'data'  => $payments->getCollection()->map(fn (Payment $payment): array => $this->formatPayment($payment))->values()->all(),
                'links' => $payments->linkCollection()->toArray(),
                'meta'  => [
                    'current_page' => $payments->currentPage(),
                    'last_page'    => $payments->lastPage(),
                    'per_page'     => $payments->perPage(),
                    'total'        => $payments->total(),
                    'from'         => $payments->firstItem(),
                    'to'           => $payments->lastItem(),
                ],
            ],
            'filters' => $request->safe()->only(['status', 'from', 'to']),
        ]);
    }

    /**
     * @param  Builder<Payment>  $query
     */
    private function applyFilters(Builder $query, ListPaymentHistoryRequest $request): void
    {
        if ($request->filled('status')) {
            $status = PaymentStatusEnum::tryFrom($request->string('status')->value());
            if ($status !== null) {
                $query->where('transaction_status', $status->value);
            }
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->string('from')->value());
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->string('to')->value());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formatPayment(Payment $payment): array
    {
        return [
            'id'                 => $payment->getKey(),
            'order_reference'    => $payment->order_reference,
            'amount'             => $payment->amount,
            'currency'           => $payment->currency,
            'transaction_status' => $payment->transaction_status?->value,
            'fee_amount'         => $payment->fee_amount,
            'fee_percentage'     => $payment->fee_percentage,
            'net_amount'         => $payment->net_amount,
            'payment_system'     => $payment->payment_system,
            'refunded_at'        => $payment->refunded_at?->toIso8601String(),
            'refund_amount'      => $payment->refund_amount,
            'created_at'         => $payment->created_at?->toIso8601String(),
            'payable'            => $payment->payable ? [
                'type'  => class_basename($payment->payable_type ?? ''),
                'id'    => $payment->payable->getKey(),
                'label' => method_exists($payment->payable, 'getPayableLabel')
                    ? $payment->payable->getPayableLabel()
                    : '',
            ] : null,
        ];
    }
}
