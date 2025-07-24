<?php

namespace App\Models;

use App\Enums\TagEnum;
use Database\Factories\MentorTagFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @mixin IdeHelperMentorTag
 */
#[UseFactory(MentorTagFactory::class)]
class MentorTag extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => TagEnum::class,
        ];
    }
    public function mentorProfiles(): BelongsToMany
    {
        return $this->belongsToMany(MentorProfile::class, 'mentor_profile_mentor_tag');
    }
}
