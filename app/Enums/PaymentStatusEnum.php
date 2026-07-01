<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatusEnum: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case DECLINED = 'declined';
    case REFUNDED = 'refunded';
    case EXPIRED = 'expired';

    public static function fromWayForPay(string $status): self
    {
        return match ($status) {
            'Approved'                      => self::APPROVED,
            'Declined', 'Voided', 'Expired' => self::DECLINED,
            'Refunded'                      => self::REFUNDED,
            default                         => self::PENDING,
        };
    }

    public function isTerminal(): bool
    {
        return match ($this) {
            self::APPROVED, self::DECLINED, self::REFUNDED, self::EXPIRED => true,
            self::PENDING                                                 => false,
        };
    }
}
