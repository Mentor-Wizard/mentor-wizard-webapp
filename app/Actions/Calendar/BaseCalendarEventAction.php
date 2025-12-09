<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Http\Requests\Calendar\EditCalendarEventRequest;
use App\Http\Requests\Calendar\StoreCalendarEventRequest;
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
        $profile = $user?->profile;
        $userTimezone = $profile ? $profile->timezone : config('app.timezone');

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

        return [
            'title'           => $validated['title'],
            'start_date_time' => $startDateTimeUTC,
            'end_date_time'   => $endDateTimeUTC,
            'type'            => $eventType,
            'colour'          => $validated['colour'],
            'description'     => $validated['description'],
            'status'          => CalendarEventStatusEnum::CONFIRMED,
            'date'            => $startDateTimeUTC?->format('Y-m-d'),
        ];
    }
}
