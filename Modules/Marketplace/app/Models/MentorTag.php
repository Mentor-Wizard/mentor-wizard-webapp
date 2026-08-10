<?php

declare(strict_types=1);

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Marketplace\Database\Factories\MentorTagFactory;
use Modules\Marketplace\Enums\TagEnum;
use Override;

/**
 * @mixin IdeHelperMentorTag
 */
#[UseFactory(MentorTagFactory::class)]
#[Fillable([
    'tag',
    'type',
])]
class MentorTag extends Model
{
    /** @use HasFactory<MentorTagFactory> */
    use HasFactory;

    /**
     * @return BelongsToMany<MentorProfile, $this>
     */
    public function mentorProfiles(): BelongsToMany
    {
        return $this->belongsToMany(MentorProfile::class, 'mentor_profile_mentor_tag');
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'type' => TagEnum::class,
        ];
    }
}
