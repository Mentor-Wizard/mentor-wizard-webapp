<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentStatusEnum;
use Carbon\CarbonInterface;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Override;

/**
 * @property PaymentStatusEnum|null $transaction_status
 * @property CarbonInterface|null $refunded_at
 *
 * @mixin IdeHelperPayment
 */
#[UseFactory(PaymentFactory::class)]
#[Fillable([
    'payable_type',
    'payable_id',
    'order_reference',
    'amount',
    'currency',
    'transaction_status',
    'reason',
    'reason_code',
    'payment_system',
    'card_type',
    'issue_bank_name',
    'fee_amount',
    'fee_percentage',
    'net_amount',
    'refunded_at',
    'refund_amount',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * @return MorphTo<Model, $this>
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'amount'             => 'int',
            'currency'           => 'string',
            'transaction_status' => PaymentStatusEnum::class,
            'reason'             => 'string',
            'reason_code'        => 'string',
            'payment_system'     => 'string',
            'card_type'          => 'string',
            'issue_bank_name'    => 'string',
            'fee_amount'         => 'int',
            'fee_percentage'     => 'float',
            'net_amount'         => 'int',
            'refunded_at'        => 'datetime',
            'refund_amount'      => 'int',
        ];
    }
}
