<?php

declare(strict_types=1);

namespace App\Enums\Payment;

enum PaymentGatewayEnum: string
{
    case WayForPay = 'wayforpay';
    case LiqPay = 'liqpay';
    case Stripe = 'stripe';

    /**
     * @return PaymentGatewayEnum[]
     */
    public static function available(): array
    {
        return array_filter(
            self::cases(),
            fn (self $gateway): bool => $gateway->isEnabled()
        );
    }

    public static function default(): self
    {
        return self::from(config('payment.default'));
    }

    public function label(): string
    {
        return match ($this) {
            self::WayForPay => 'WayForPay',
            self::LiqPay    => 'LiqPay',
            self::Stripe    => 'Stripe',
        };
    }

    public function isEnabled(): bool
    {
        return config(sprintf('payment.gateways.%s.enabled', $this->value), false);
    }
}
