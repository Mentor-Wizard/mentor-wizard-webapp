<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\CalendarEventRoleEnum;
use App\Models\CalendarEvent;
use App\Models\User;

final class CalendarEventPolicy
{

    public function create(User $user): bool
    {
        return $user->hasRole('mentor');
    }

    public function update(User $user, CalendarEvent $calendarEvent): bool
    {
        return $user->hasRole('mentor')
            && $calendarEvent->calendarEventUsers()
                ->where('user_id', $user->getKey())
                ->where('role', CalendarEventRoleEnum::HOST->value)
                ->exists();
    }

    public function delete(User $user, CalendarEvent $calendarEvent): bool
    {
        return $this->update($user, $calendarEvent);
    }

    public function view(User $user, CalendarEvent $calendarEvent): bool
    {
        return $calendarEvent->calendarEventUsers()
            ->where('user_id', $user->getKey())
            ->exists();
    }
}
