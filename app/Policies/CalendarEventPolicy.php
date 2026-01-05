<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\CalendarEventRoleEnum;
use App\Models\CalendarEvent;
use App\Models\User;

final class CalendarEventPolicy
{

    public function update(User $user, CalendarEvent $calendarEvent): bool
    {
        if ($calendarEvent->relationLoaded('calendarEventUsers')) {
            return $calendarEvent->calendarEventUsers
                ->where('id', $user->getKey())
                ->whereIn('pivot.role', [CalendarEventRoleEnum::HOST->value,
                    CalendarEventRoleEnum::COHOST->value,
                    CalendarEventRoleEnum::MENTI->value])
                ->isNotEmpty();
        }

        return $calendarEvent->calendarEventUsers()
            ->where('user_id', $user->getKey())
            ->whereIn('role', [CalendarEventRoleEnum::HOST->value,
                CalendarEventRoleEnum::COHOST->value,
                CalendarEventRoleEnum::MENTI->value])
            ->exists();
    }

    public function delete(User $user, CalendarEvent $calendarEvent): bool
    {
        return $this->update($user, $calendarEvent);
    }

    public function view(User $user, CalendarEvent $calendarEvent): bool
    {
        if ($calendarEvent->relationLoaded('calendarEventUsers')) {
            return $calendarEvent->calendarEventUsers
                ->where('id', $user->getKey())
                ->isNotEmpty();
        }

        return $calendarEvent->calendarEventUsers()
            ->where('user_id', $user->getKey())
            ->exists();
    }
}
