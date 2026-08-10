<?php

declare(strict_types=1);

namespace Modules\MentorProgram\Observers;

use Illuminate\Support\Str;
use Modules\MentorProgram\Models\MentorProgram;

class MentorProgramObserver
{
    public function created(MentorProgram $mentorProgram): void
    {
        $isFirstProgram = ! MentorProgram::query()
            ->where('mentor_id', $mentorProgram->mentor_id)
            ->where('id', '!=', $mentorProgram->getKey())
            ->where('is_main', true)
            ->exists();

        if ($isFirstProgram) {
            $mentorProgram->is_main = true;
            $mentorProgram->saveQuietly();
        }
    }

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
