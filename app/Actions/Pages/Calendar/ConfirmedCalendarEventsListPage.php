<?php

declare(strict_types=1);

namespace App\Actions\Pages\Calendar;

use App\Enums\CalendarEventStatusEnum;
use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;

class ConfirmedCalendarEventsListPage
{
    use AsController;

    public function handle(Request $request, ?MentorProgram $mentorProgram = null): Response
    {
        $user = $request->user();

        $query = $user->calendarEvents()
            ->where('status', CalendarEventStatusEnum::CONFIRMED->value)
            ->with(['mentorProgram:id,name', 'participants:id,username']);

        if ($mentorProgram instanceof MentorProgram) {
            $query->where('mentor_program_id', $mentorProgram->getKey());
        }

        $events = $query->orderBy('start_date_time')->get();

        $upcomingCalendarEvents = $events
            ->filter(fn (CalendarEvent $event): bool => $event->start_date_time >= today())
            ->groupBy(fn (CalendarEvent $event): int => $event->mentor_program_id ?? 0)
            ->map(fn (Collection $group): array => [
                'name'   => $group->first()->mentorProgram?->name,
                'events' => $group->values(),
            ]);

        $pastCalendarEvents = $events
            ->filter(fn (CalendarEvent $event): bool => $event->start_date_time < today())
            ->sortByDesc('start_date_time')
            ->groupBy(fn (CalendarEvent $event): int => $event->mentor_program_id ?? 0)
            ->map(fn (Collection $group): array => [
                'name'   => $group->first()->mentorProgram?->name,
                'events' => $group->values(),
            ]);

        return Inertia::render('Calendar/ListConfirmedCalendarEventsPage', [
            'locale'                 => app()->getLocale(),
            'upcomingCalendarEvents' => $upcomingCalendarEvents,
            'pastCalendarEvents'     => $pastCalendarEvents,
        ]);
    }
}
