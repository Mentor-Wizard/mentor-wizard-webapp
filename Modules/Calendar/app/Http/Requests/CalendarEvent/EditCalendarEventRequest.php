<?php

declare(strict_types=1);

namespace Modules\Calendar\Http\Requests\CalendarEvent;

use App\Models\MentorProgram;
use Illuminate\Contracts\Validation\Validator;
use Modules\Calendar\Services\CheckTimeSlotReservedService;
use Modules\Calendar\Traits\CalendarEventRequestRules;

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
