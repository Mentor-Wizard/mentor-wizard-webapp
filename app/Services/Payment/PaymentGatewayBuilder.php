<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Enums\Payment\PaymentGatewayEnum;
use App\Interfaces\PaymentGatewayInterface;
use InvalidArgumentException;
use RuntimeException;

class PaymentGatewayBuilder
{
    public function make(?string $gateway = null): PaymentGatewayInterface
    {
        $gateway ??= config('payment.default');
        $gatewayEnum = PaymentGatewayEnum::from($gateway);

        throw_unless($gatewayEnum->isEnabled(), InvalidArgumentException::class, sprintf('Payment gateway [%s] is not enabled. Check .env configuration.', $gateway));

        return match ($gatewayEnum) {
            PaymentGatewayEnum::WayForPay => $this->createWayForPayGateway(),
            PaymentGatewayEnum::LiqPay    => throw new RuntimeException('LiqPay not implemented yet'),
        };
    }

    /**
     * Get list of available payment gateways for frontend
     */
    public function available(): array
    {
        return collect(PaymentGatewayEnum::available())
            ->mapWithKeys(fn (PaymentGatewayEnum $gateway): array => [
                $gateway->value => [
                    'name'    => $gateway->label(),
                    'value'   => $gateway->value,
                    'enabled' => $gateway->isEnabled(),
                ],
            ])
            ->all();
    }

    private function createWayForPayGateway(): WayForPayGateway
    {
        $config = config('payment.gateways.wayforpay');

        throw_if(empty($config['merchant_account']) || empty($config['merchant_secret_key']), InvalidArgumentException::class, 'WayForPay credentials not configured. Check WAYFORPAY_* env variables.');

        return new WayForPayGateway(
            merchantAccount: $config['merchant_account'],
            merchantSecretKey: $config['merchant_secret_key'],
            merchantDomain: $config['merchant_domain'],
            serviceUrl: $config['service_url'],
            returnUrl: $config['return_url'],
        );
    }
}
