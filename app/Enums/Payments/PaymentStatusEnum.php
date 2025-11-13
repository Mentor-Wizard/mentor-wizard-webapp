<?php

declare(strict_types=1);

namespace App\Enums\Payments;

enum PaymentStatusEnum: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Declined = 'declined';
    case Processing = 'processing';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';
    case Failed = 'failed';
    case Expired = 'expired';

    // TODO: Implement translation labels for payment statuses
    public function label(): string
    {
        return match ($this) {
            self::Pending           => 'Очікується',
            self::Approved          => 'Успішно',
            self::Declined          => 'Відхилено',
            self::Processing        => 'Обробляється',
            self::Refunded          => 'Повернено',
            self::PartiallyRefunded => 'Частково повернено',
            self::Failed            => 'Помилка',
            self::Expired           => 'Прострочено',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending    => 'warning',
            self::Approved   => 'success',
            self::Declined   => 'danger',
            self::Processing => 'primary',
            self::Refunded, self::PartiallyRefunded => 'info',
            self::Failed, self::Expired => 'danger',
        };
    }
}
