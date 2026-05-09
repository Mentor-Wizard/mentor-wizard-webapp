<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\MentorProgram;
use App\Models\User;

class MentorProgramPolicy
{
    public function view(User $user, MentorProgram $mentorProgram): bool
    {
        return $mentorProgram->mentor_id === $user->getKey();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('mentor');
    }

    public function update(User $user, MentorProgram $mentorProgram): bool
    {
        return $mentorProgram->mentor_id === $user->getKey();
    }

    public function delete(User $user, MentorProgram $mentorProgram): bool
    {
        return $mentorProgram->mentor_id === $user->getKey();
    }
}
