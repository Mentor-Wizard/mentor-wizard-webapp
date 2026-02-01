<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\MentorProgram;
use Illuminate\Support\Str;

class MentorProgramObserver
{
    public function saved(MentorProgram $mentorProgram): void
    {
        if (empty($mentorProgram->slug) && ! empty($mentorProgram->name)) {
            $baseSlug = Str::slug($mentorProgram->name);
            $slug = $baseSlug;
            $counter = 1;

            while (MentorProgram::query()->where('slug', $slug)->exists()) {
                $slug = $baseSlug.'-'.$counter;
                $counter++;
            }

            $mentorProgram->slug = $slug;
            $mentorProgram->saveQuietly();
        }
    }
}
