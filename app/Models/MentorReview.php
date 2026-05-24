<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Database\Factories\MentorReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperMentorReview
 */
#[Fillable([
    'mentor_id',
    'menti_id',
    'comment',
    'rating',
])]
class MentorReview extends Model
{
    /** @use HasFactory<MentorReviewFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function menti(): BelongsTo
    {
        return $this->belongsTo(User::class, 'menti_id');
    }
}
