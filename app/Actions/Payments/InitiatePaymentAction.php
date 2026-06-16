<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Contracts\Payable;
use App\Enums\PaymentStatusEnum;
use App\Http\Requests\Payments\InitiatePaymentRequest;
use App\Models\MentorProgram;
use App\Models\MentorSession;
use App\Models\Payment;
use App\Support\CurrencyConverter;
use AratKruglik\WayForPay\Domain\Client;
use AratKruglik\WayForPay\Domain\Product;
use AratKruglik\WayForPay\Domain\Transaction;
use AratKruglik\WayForPay\Facades\WayForPay;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsController;

class InitiatePaymentAction
{
    use AsController;

    public function handle(InitiatePaymentRequest $request): Response
    {
        $payable = $request->resolvePayableModel();

        Gate::authorize('initiate-payment', $payable);

        [$amount, $currency, $label] = $this->resolvePayableDetails($payable);

        $orderReference = Str::uuid()->toString();

        $payment = new Payment([
            'payable_type'       => $payable::class,
            'payable_id'         => $payable->getKey(),
            'order_reference'    => $orderReference,
            'amount'             => CurrencyConverter::toCents($amount),
            'currency'           => $currency,
            'transaction_status' => PaymentStatusEnum::PENDING,
            'fee_amount'         => null,
            'fee_percentage'     => null,
            'net_amount'         => null,
        ]);

        $payment->save();

        $user = $request->user();

        $client = new Client(
            nameFirst: $user->name,
            nameLast: '',
            email: $user->email,
            phone: '',
        );

        $transaction = new Transaction(
            orderReference: $orderReference,
            amount: $amount,
            currency: $currency,
            orderDate: Date::now()->getTimestamp(),
            client: $client,
        );

        $transaction->addProduct(new Product(
            name: $label,
            price: $amount,
            count: 1,
        ));

        $html = WayForPay::purchase(
            $transaction,
            returnUrl: route('payments.success'),
            serviceUrl: route('payments.webhook'),
        );

        return response($html, 200)->header('Content-Type', 'text/html');
    }

    /**
     * @return array{float, string, string}
     */
    private function resolvePayableDetails(Payable $payable): array
    {
        if ($payable instanceof MentorSession) {
            $program = $payable->mentorProgram()->with('currency')->first();
            $currencyModel = $program?->currency;
            $currency = $currencyModel !== null ? $currencyModel->name : 'UAH';

            return [(float) $payable->cost, $currency, $payable->getPayableLabel()];
        }

        if ($payable instanceof MentorProgram) {
            $payable->loadMissing('currency');
            $currencyModel = $payable->currency;
            $currency = $currencyModel !== null ? $currencyModel->name : 'UAH';

            return [(float) $payable->cost, $currency, $payable->getPayableLabel()];
        }

        return [0.0, 'UAH', ''];
    }
}
