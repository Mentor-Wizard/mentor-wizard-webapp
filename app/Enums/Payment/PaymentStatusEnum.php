<?php

declare(strict_types=1);

namespace App\Enums\Payment;

enum PaymentStatusEnum: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Declined = 'declined';
    case Processing = 'processing';
    case Refunded = 'refunded';
    case Failed = 'failed';
}
