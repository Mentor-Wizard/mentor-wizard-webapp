<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\MentorProgram;
use Str;

class MentorProgramObserver
{
    public function saving(MentorProgram $mentorProgram): void
    {
        if (empty($mentorProgram->slug) && ! empty($mentorProgram->name)) {
            $mentorProgram->slug = Str::slug($mentorProgram->name);
        }
    }
}
