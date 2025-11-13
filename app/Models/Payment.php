<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Payments\PaymentStatusEnum;
use App\Enums\Payments\PaymentTypeEnum;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperPayment
 */
#[UseFactory(PaymentFactory::class)]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'mentor_session_id',
        'transaction_id',
        'order_reference',
        'amount',
        'currency',
        'transaction_status',
        'payment_type',
        'refund_amount',
        'refunded_at',
        'reason',
        'reason_code',
        'payment_system',
        'card_type',
        'card_pan',
        'issue_bank_name',
        'phone',
        'account_number',
        'rectoken',
        'is_regular',
        'parent_payment_id',
        'metadata',
    ];

    /**
     * @return BelongsTo<MentorSession, $this>
     */
    public function mentorSession(): BelongsTo
    {
        return $this->belongsTo(MentorSession::class, 'mentor_session_id');
    }

    /**
     * Get the parent payment that this payment is a refund of.
     */
    public function parentPayment(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_payment_id');
    }

    /**
     * Get all refund payments associated with this payment.
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(self::class, 'parent_payment_id');
    }

    // TODO: Implement payment status helper methods
    // - isApproved(): bool - Check if payment status is approved
    // - isPending(): bool - Check if payment status is pending
    // - isDeclined(): bool - Check if payment status is declined
    // - isRefunded(): bool - Check if payment has been refunded
    // - isRefundable(): bool - Check if payment can be refunded (approved and not already refunded)
    // - getRefundableAmount(): int - Calculate remaining refundable amount
    // etc

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mentor_session_id'  => 'int',
            'transaction_id'     => 'string',
            'order_reference'    => 'string',
            'amount'             => 'int',
            'currency'           => 'string',
            'transaction_status' => PaymentStatusEnum::class,
            'payment_type'       => PaymentTypeEnum::class,
            'refund_amount'      => 'int',
            'refunded_at'        => 'datetime',
            'reason'             => 'string',
            'reason_code'        => 'string',
            'payment_system'     => 'string',
            'card_type'          => 'string',
            'card_pan'           => 'string',
            'issue_bank_name'    => 'string',
            'phone'              => 'string',
            'account_number'     => 'string',
            'rectoken'           => 'string',
            'is_regular'         => 'boolean',
            'parent_payment_id'  => 'int',
            'metadata'           => 'array',
        ];
    }
}
