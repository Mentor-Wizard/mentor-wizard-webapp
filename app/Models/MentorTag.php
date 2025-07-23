<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperMentorTag
 */
class MentorTag extends Model
{
    /** @use HasFactory<\Database\Factories\MentorTagFactory> */
    use HasFactory;

    const LANGUAGE = 'language';
    const STACK = 'stack';

    public function mentorProfiles()
    {
        return $this->belongsToMany(MentorProfile::class, 'mentor_profile_mentor_tag');
    }
}
