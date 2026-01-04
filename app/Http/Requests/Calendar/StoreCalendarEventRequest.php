<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar;

use App\Services\Calendar\CheckTimeSlotReservedService;
use App\Traits\Calendar\CalendarEventRequestRules;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Date;

class StoreCalendarEventRequest extends FormRequest
{
    use CalendarEventRequestRules;

    public function authorize(): bool
    {
        return true;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            $user = auth()->user();
            $timezone = $user->profile->timezone;

            if (! $validator->errors()->hasAny(['fromDate', 'fromTime', 'toDate', 'toTime'])) {
                $startDate = Date::createFromFormat(
                    '!Y-m-d H:i',
                    $this->input('fromDate').$this->input('fromTime'),
                    $timezone
                );

                $endDate = Date::createFromFormat(
                    '!Y-m-d H:i',
                    $this->input('toDate').$this->input('toTime'),
                    $timezone
                );

                $isWithinAvailableSlots = new CheckTimeSlotReservedService(
                    $startDate,
                    $endDate,
                    $timezone,
                    auth()->user()
                )->isSlotAvailable();

                if (! $isWithinAvailableSlots) {
                    $validator->errors()->add('fromDate', 'there are another events on this time');
                }
            }
        });
    }

    public function getEventData(): array
    {
        $validated = $this->validated();

        try {
            $startDateTime = Date::createFromFormat(
                'Y-m-d H:i',
                $validated['fromDate'].' '.$validated['fromTime'],
                $validated['timezone']
            )?->setTimezone(config('app.timezone'));
            $endDateTime = Date::createFromFormat(
                'Y-m-d H:i',
                $validated['toDate'].' '.$validated['toTime'],
                $validated['timezone']
            )?->setTimezone(config('app.timezone'));
        } catch (Exception) {
            return [];
        }

        $duration = (int) $startDateTime?->diffInSeconds($endDateTime);
        $eventType = match ($validated['type']) {
            'individual' => CalendarEventTypeEnum::INDIVIDUAL->value,
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
