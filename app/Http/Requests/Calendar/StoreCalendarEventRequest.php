<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar;

use App\Services\Calendar\CheckTimeSlotReservedService;
use App\Traits\Calendar\CalendarEventRequestRules;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

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

            // Get timezone from user profile
            $user = auth()->user();
            $profile = $user?->profile;
            $timezone = $profile ? $profile->timezone : config('app.timezone');

            if (! array_intersect(array_keys($validator->errors()->messages()),
                ['fromDate', 'fromTime', 'toDate', 'toTime'])) {
                $isWithinAvailableSlots = new CheckTimeSlotReservedService(
                    $this->input('fromDate'),
                    $this->input('fromTime'),
                    $this->input('toDate'),
                    $this->input('toTime'),
                    $timezone,
                    auth()->user())->isSlotAvailable();

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
