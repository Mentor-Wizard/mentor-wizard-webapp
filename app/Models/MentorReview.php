<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MentorReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperMentorReview
 */
class MentorReview extends Model
{
    /** @use HasFactory<MentorReviewFactory> */
    use HasFactory;

    protected $fillable = [
        'mentor_id',
        'menti_id',
        'comment',
        'rating',
    ];

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function menti(): BelongsTo
    {
        return $this->belongsTo(User::class, 'menti_id');
    }
}
