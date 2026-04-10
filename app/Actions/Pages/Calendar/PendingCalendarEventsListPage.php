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
            ->where('status', CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION->value)
            ->with(['mentorProgram:id,name', 'participants:id,username'])
            ->orderBy('start_date_time');

        if ($mentorProgram instanceof MentorProgram) {
            $query->where('mentor_program_id', $mentorProgram->id);
        }

        $events = $query->get();

        $upcomingCalendarEvents = $events
            ->filter(fn (CalendarEvent $event): bool => $event->start_date_time >= today())
            ->groupBy(fn (CalendarEvent $event): string => $event->mentorProgram->name ?? 'Unknown Program');

        $pastCalendarEvents = $events
            ->filter(fn (CalendarEvent $event): bool => $event->start_date_time < today())
            ->sortByDesc('start_date_time')
            ->groupBy(fn (CalendarEvent $event): string => $event->mentorProgram->name ?? 'Unknown Program');

        return Inertia::render('Calendar/ListPendingCalendarEventsPage', [
            'locale'                 => app()->getLocale(),
            'upcomingCalendarEvents' => $upcomingCalendarEvents,
            'pastCalendarEvents'     => $pastCalendarEvents,
        ]);
    }
}
