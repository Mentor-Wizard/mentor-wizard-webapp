<?php

declare(strict_types=1);

namespace Modules\Marketplace\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Marketplace\Database\Factories\MentorReviewFactory;

/**
 * @mixin IdeHelperMentorReview
 */
#[UseFactory(MentorReviewFactory::class)]
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
