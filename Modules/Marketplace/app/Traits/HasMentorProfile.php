<?php

declare(strict_types=1);

namespace Modules\Marketplace\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Marketplace\Models\MentorProfile;
use Modules\Marketplace\Models\MentorReview;

/**
 * @phpstan-require-extends Model
 */
trait HasMentorProfile
{
    /**
     * @return HasOne<MentorProfile, $this>
     */
    public function mentorProfile(): HasOne
    {
        return $this->hasOne(MentorProfile::class);
    }

    /**
     * @return HasMany<MentorReview, $this>
     */
    public function mentorReviews(): HasMany
    {
        return $this->hasMany(MentorReview::class, 'mentor_id');
    }

    /**
     * @return HasMany<MentorReview, $this>
     */
    public function reviewsByMenti(): HasMany
    {
        return $this->hasMany(MentorReview::class, 'menti_id');
    }

    /**
     * @return Attribute<float, never>
     */
    protected function rating(): Attribute
    {
        return Attribute::make(
            get: fn (): float => (float) $this->mentorReviews()->avg('rating'),
        );
    }
}
