<?php

declare(strict_types=1);

namespace App\Enums\Payments;

enum PaymentTypeEnum: string
{
    case Purchase = 'purchase';
    case Refund = 'refund';
    case Account2card = 'account2card';
    case Account2account = 'account2account';
    case Regular = 'regular';

    // TODO: Implement translation labels for payment types
    public function label(): string
    {
        return match ($this) {
            self::Purchase        => 'Оплата',
            self::Refund          => 'Повернення',
            self::Account2card    => 'Переказ на карту',
            self::Account2account => 'Переказ на рахунок',
            self::Regular         => 'Регулярний платіж',
        };
    }
}
