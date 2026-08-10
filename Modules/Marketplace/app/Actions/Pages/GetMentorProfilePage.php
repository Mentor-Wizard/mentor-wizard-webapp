<?php

declare(strict_types=1);

namespace Modules\Marketplace\Actions\Pages;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;
use Modules\Calendar\Services\BookingCalendarEventsService;
use Modules\Marketplace\Http\Resources\MentorProfilePageResource;
use Modules\MentorProgram\Models\MentorProgram;

class GetMentorProfilePage
{
    use AsController;

    const int PER_PAGE = 4;

    public function handle(Request $request, User $mentor): Response
    {
        throw_unless($mentor->hasRole(RoleEnum::MENTOR->value), ModelNotFoundException::class);

        $mentor->loadMissing(['mentorPrograms', 'profile']);
        $mainProgram = $mentor->mentorPrograms->where('is_main', true)->load('mentor')->first();

        $calendarBlock = null;
        $currentDate = null;

        if ($mainProgram instanceof MentorProgram) {
            $user = auth()->user();
            $timezone = $user?->profile->timezone ?? config('app.timezone');
            $userForService = $user ?? $mentor;
            $dateInput = $request->get('calendar_date');
            $date = $dateInput ? Date::parse($dateInput, $timezone) : Date::now($timezone);
            $currentDate = $date->toDateString();

            $calendarBlock = new BookingCalendarEventsService(
                $date,
                $userForService,
                $timezone,
                true,
                $mainProgram,
            )->getFormattedMonthAvailableSlots();
        }

        return Inertia::render('Marketplace/Mentor/ViewPage', [
            'mentor'          => MentorProfilePageResource::make($mentor),
            'calendarBlock'   => $calendarBlock,
            'mainProgramSlug' => $mainProgram?->slug,
            'mainProgram'     => $mainProgram ? [
                'id'                       => $mainProgram->getKey(),
                'name'                     => $mainProgram->name,
                'description'              => $mainProgram->description,
                'session_type_options'     => $mainProgram->session_type_options,
                'session_duration'         => $mainProgram->session_duration,
            ] : null,
            'currentDate'     => $currentDate,
            'weekDays'        => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'],
        ]);
    }
}
