<?php

declare(strict_types=1);

namespace App\Actions\Pages\Calendar;

use App\Enums\CalendarEventStatusEnum;
use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;

class PendingCalendarEventsListPage
{
    use AsController;

    public function handle(?MentorProgram $mentorProgram = null): Response
    {
        $user = auth()->user();

        $query = $user->calendarEvents()
            ->where('status', CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION->value);

        if ($mentorProgram instanceof MentorProgram) {
            $query->where('mentor_program_id', $mentorProgram->id);
        }

        $query->with(['mentorProgram:id,name']);

        $events = $query->get();

        $grouped = $events->groupBy(fn (CalendarEvent $event) => $event->mentorProgram?->name ?? 'No program');

        return Inertia::render('Calendar/ListPendingCalendarEventsPage', [
            'locale'            => app()->getLocale(),
            'calendarEvents'    => $grouped,
        ]);
    }
}
