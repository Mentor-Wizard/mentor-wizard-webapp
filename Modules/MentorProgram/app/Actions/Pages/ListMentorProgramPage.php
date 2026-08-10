<?php

declare(strict_types=1);

namespace Modules\MentorProgram\Actions\Pages;

use Illuminate\Support\Facades\Date;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;
use Modules\Calendar\Enums\CalendarEventStatusEnum;

class ListMentorProgramPage
{
    use AsController;

    public function handle(): Response
    {
        $programs = auth()
            ->user()
            ->mentorPrograms()
            ->select('id', 'name', 'slug', 'is_main', 'description', 'cost', 'currency_id', 'created_at')
            ->with(['currency:id,symbol'])
            ->withCount([
                'calendarEvents as pending_events_requests_number' => fn ($q) => $q
                    ->where('status', CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION)
                    ->where('start_date_time', '>=', Date::today()),
                'calendarEvents as confirmed_events_number' => fn ($q) => $q
                    ->where('status', CalendarEventStatusEnum::CONFIRMED)
                    ->where('start_date_time', '>=', Date::today()),
            ])
            ->orderByDesc('is_main')
            ->orderByDesc('created_at')
            ->get()
            ->toArray();

        return Inertia::render('MentorProgram/ListPage', [
            'programs' => $programs,
        ]);
    }
}
