<?php

declare(strict_types=1);

namespace Modules\Calendar\Http\Requests\CalendarEvent;

use App\Models\MentorProgram;
use Illuminate\Contracts\Validation\Validator;
use Modules\Calendar\Models\CalendarEvent;
use Modules\Calendar\Services\CheckBookingSlotService;
use Modules\Calendar\Traits\CalendarEventRequestRules;

class StoreCalendarEventRequest extends CalendarEventRequest
{
    use CalendarEventRequestRules;

    public function authorize(): bool
    {
        if (! $this->user()->can('create', CalendarEvent::class)) {
            return false;
        }

        $mentorProgramId = $this->input('mentor_program_id');
        /** @var MentorProgram|null $mentorProgram */
        $mentorProgram = MentorProgram::query()->find($mentorProgramId);

        if ($mentorProgram === null) {
            return true;
        }

        return auth()->user()->getKey() !== $mentorProgram->mentor_id;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            $timezone = auth()->user()->profile->timezone;
            $isMultiDay = $this->input('fromDate') !== $this->input('toDate');

            if (! $isMultiDay && ! $validator->errors()->hasAny(['fromDate', 'fromTime', 'toDate', 'toTime', 'mentor_program_id'])) {
                $this->validateSlotAvailability($validator, $timezone);
            }
        });
    }

    private function validateSlotAvailability(Validator $validator, string $timezone): void
    {
        /** @var MentorProgram $mentorProgram */
        $mentorProgram = MentorProgram::query()->findOrFail($this->input('mentor_program_id'));

        if ($this->filled('selectedDuration') && $this->integer('selectedDuration') !== $mentorProgram->session_duration) {
            $validator->errors()->add('selectedDuration', 'Selected duration must match the program session duration.');
        }

        ['startDate' => $startDate, 'endDate' => $endDate] = $this->getFormattedDates($timezone);
        $sessionDuration = $this->integer('selectedDuration') ?: $mentorProgram->session_duration;

        $isValidSlot = new CheckBookingSlotService(
            $startDate,
            $endDate,
            $mentorProgram,
            $sessionDuration,
        )->isValidSlot();

        if (! $isValidSlot) {
            $validator->errors()->add('fromDate', 'This slot is busy');
        }
    }
}
