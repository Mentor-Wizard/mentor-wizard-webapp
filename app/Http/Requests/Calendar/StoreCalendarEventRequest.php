<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar;

use App\Models\MentorProgram;
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

            $mentorProgram = $this->input('mentor_program_id') ?
                MentorProgram::query()->find($this->input('mentor_program_id')) : null;
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
                    auth()->user(),
                    [],
                    $mentorProgram
                )->isSlotAvailable();

                if (! $isWithinAvailableSlots) {
                    $validator->errors()->add('fromDate', 'there are another events on this time');
                }
            }
        });
    }
}
