<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TagEnum;
use Database\Factories\MentorTagFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Override;

/**
 * @mixin IdeHelperMentorTag
 */
#[UseFactory(MentorTagFactory::class)]
class MentorTag extends Model
{
    /** @use HasFactory<MentorTagFactory> */
    use HasFactory;

    protected $fillable = [
        'tag',
        'type',
    ];

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
