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

    protected function getCalendarEventData(StoreCalendarEventRequest|EditCalendarEventRequest $request): array
    {
        $validated = $request->validated();

        $startDateTime = Date::createFromFormat(
            'Y-m-d H:i',
            $validated['fromDate'].' '.$validated['fromTime'],
            $validated['timezone']
        );
        $endDateTime = Date::createFromFormat(
            'Y-m-d H:i',
            $validated['toDate'].' '.$validated['toTime'],
            $validated['timezone']
        );

        $duration = (int) $startDateTime?->diffInSeconds($endDateTime);
        $eventType = match ($validated['type']) {
            'group'      => CalendarEventTypeEnum::GROUP->value,
            default      => CalendarEventTypeEnum::INDIVIDUAL->value,
        };

        return [
            'title'           => $validated['title'],
            'start_date_time' => $startDateTime,
            'end_date_time'   => $endDateTime,
            'duration'        => $duration,
            'type'            => $eventType,
            'colour'          => $validated['colour'],
            'description'     => $validated['description'],
            'status'          => CalendarEventStatusEnum::CONFIRMED,
            'date'            => $startDateTime?->format('Y-m-d'),
        ];
    }
}
