<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar\CalendarEvent;

use App\Models\MentorProgram;
use App\Services\Calendar\CheckTimeSlotReservedService;
use App\Traits\Calendar\CalendarEventRequestRules;
use Illuminate\Contracts\Validation\Validator;

class EditCalendarEventRequest extends CalendarEventRequest
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
            $profile = $user->profile;
            $timezone = $profile->timezone;

            if (! $validator->errors()->hasAny(['fromDate', 'fromTime', 'toDate', 'toTime', 'mentor_program_id'])) {
                /** @var MentorProgram $mentorProgram */
                $mentorProgram = MentorProgram::query()->findOrFail($this->input('mentor_program_id'));
                ['startDate' => $startDate, 'endDate' => $endDate] = $this->getFormattedDates($timezone);

                $isWithinAvailableSlots = new CheckTimeSlotReservedService(
                    $startDate,
                    $endDate,
                    $timezone,
                    auth()->user(),
                    $mentorProgram,
                    [$this->input('id')])
                    ->isSlotAvailable();

                if (! $isWithinAvailableSlots) {
                    $validator->errors()->add('fromDate', 'there are another events on this time');
                }
            }
        });
    }
}
