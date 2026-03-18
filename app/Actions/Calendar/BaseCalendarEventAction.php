<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Enums\MentorSessionTypeEnum;
use App\Http\Requests\Calendar\EditCalendarEventRequest;
use App\Http\Requests\Calendar\StoreCalendarEventRequest;
use App\Models\MentorProgram;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Date;
use Lorisleiva\Actions\Concerns\AsController;

class BaseCalendarEventAction
{
    use AsController;

    /**
     * @return array<string, mixed>
     */
    protected function getCalendarEventData(StoreCalendarEventRequest|EditCalendarEventRequest $request): array
    {
        $validated = $request->validated();

        // Get timezone from user profile
        $user = auth()->user();
        $profile = $user->profile;
        $userTimezone = $profile->timezone;
        $mentorProgramId = $validated['mentor_program_id'];

        // Parse dates in user's timezone
        $startDateTime = Date::createFromFormat(
            'Y-m-d H:i',
            $validated['fromDate'].' '.$validated['fromTime'],
            $userTimezone
        );
        $endDateTime = Date::createFromFormat(
            'Y-m-d H:i',
            $validated['toDate'].' '.$validated['toTime'],
            $userTimezone
        );

        // Convert to UTC for database storage
        $startDateTimeUTC = $startDateTime?->timezone('UTC');
        $endDateTimeUTC = $endDateTime?->timezone('UTC');

        $eventType = match ($validated['type']) {
            'group'      => CalendarEventTypeEnum::GROUP->value,
            default      => CalendarEventTypeEnum::INDIVIDUAL->value,
        };

        /** @var MentorProgram $mentorProgram */
        $mentorProgram = MentorProgram::query()->findOrFail($mentorProgramId);
        $status = $mentorProgram->need_confirmation
            ? CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION
            : CalendarEventStatusEnum::CONFIRMED;

        return [
            'title'             => Arr::get($validated, 'title'),
            'start_date_time'   => $startDateTimeUTC,
            'end_date_time'     => $endDateTimeUTC,
            'type'              => $eventType,
            'session_type'      => MentorSessionTypeEnum::from($validated['session_type']),
            'web_link'          => Arr::get($validated, 'webLink'),
            'colour'            => Arr::get($validated, 'colour'),
            'description'       => Arr::get($validated, 'description'),
            'status'            => $status,
            'date'              => $startDateTimeUTC?->format('Y-m-d'),
            'mentor_program_id' => $mentorProgramId,
        ];
    }
}
