<?php

declare(strict_types=1);

namespace App\Enums\Payment;

enum PaymentTypeEnum: string
{
    case Purchase = 'purchase';
    case Refund = 'refund';
    case Regular = 'regular';
}
