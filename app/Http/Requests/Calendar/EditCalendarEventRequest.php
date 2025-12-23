<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar;

use App\Services\Calendar\CheckTimeSlotReservedService;
use App\Traits\Calendar\CalendarEventRequestRules;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Date;

class EditCalendarEventRequest extends FormRequest
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

                $startDate = Date::createFromFormat(
                    '!Y-m-d H:i',
                    $this->input('fromDate').' '.$this->input('fromTime'),
                    $timezone
                );
                $endDate = Date::createFromFormat(
                    '!Y-m-d H:i',
                    $this->input('toDate').' '.$this->input('toTime'),
                    $timezone
                );

                $isWithinAvailableSlots = new CheckTimeSlotReservedService(
                    $startDate,
                    $endDate,
                    $timezone,
                    auth()->user(),
                    [$this->input('id')])
                    ->isSlotAvailable();

                if (! $isWithinAvailableSlots) {
                    $validator->errors()->add('fromDate', 'there are another events on this time');
                }
            }
        });
    }
}
