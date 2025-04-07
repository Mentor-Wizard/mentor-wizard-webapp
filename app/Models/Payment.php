<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'order_reference',
        'amount',
        'currency',
        'transaction_status',
        'reason',
        'reason_code',
        'payment_system',
        'card_type',
        'issue_bank_name',
    ];

    protected function casts(): array
    {
        return [
            'mentor_session_id' => 'int',
            'order_reference' => 'string',
            'amount' => 'int',
            'currency' => 'string',
            'transaction_status' => 'string',
            'reason' => 'string',
            'reason_code' => 'string',
            'payment_system' => 'string',
            'card_type' => 'string',
            'issue_bank_name' => 'string',
        ];
    }

    public function mentorSession(): BelongsTo
    {
        return $this->belongsTo(MentorSession::class, 'mentor_session_id');
    }
}
