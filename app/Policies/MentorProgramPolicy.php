<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MentorProgram;
use App\Models\User;

class MentorProgramPolicy
{
    public function update(User $user, MentorProgram $mentorProgram): bool
    {
        return $mentorProgram->mentor_id === $user->id;
    }

    public function delete(User $user, MentorProgram $mentorProgram): bool
    {
        return $mentorProgram->mentor_id === $user->id;
    }
}
