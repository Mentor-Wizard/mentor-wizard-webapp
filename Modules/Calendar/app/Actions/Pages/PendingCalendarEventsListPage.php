<?php

declare(strict_types=1);

namespace Modules\Calendar\Actions\Pages;

use App\Models\MentorProgram;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;
use Modules\Calendar\Enums\CalendarEventStatusEnum;
use Modules\Calendar\Models\CalendarEvent;

class PendingCalendarEventsListPage
{
    use AsController;

    public function handle(Request $request, ?MentorProgram $mentorProgram = null): Response
    {
        $user = $request->user()->load('calendarEvents');

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
            ->groupBy(fn (CalendarEvent $event): string => $event->mentorProgram?->name);

        $pastCalendarEvents = $events
            ->filter(fn (CalendarEvent $event): bool => $event->start_date_time < today())
            ->sortByDesc('start_date_time')
            ->groupBy(fn (CalendarEvent $event): string => $event->mentorProgram?->name);

        return Inertia::render('Calendar/ListPendingCalendarEventsPage', [
            'locale'                 => app()->getLocale(),
            'upcomingCalendarEvents' => $upcomingCalendarEvents,
            'pastCalendarEvents'     => $pastCalendarEvents,
        ]);
    }
}
