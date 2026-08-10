<?php

declare(strict_types=1);

namespace Modules\MentorProgram\Policies;

use App\Models\User;
use Modules\MentorProgram\Models\MentorProgram;

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
