<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar;

use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use App\Services\Calendar\CheckBookingSlotService;
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
            $user = auth()->user();
            $timezone = $user->profile->timezone;

            if (! $validator->errors()->hasAny(['fromDate', 'fromTime', 'toDate', 'toTime', 'mentor_program_id'])) {
                /** @var MentorProgram $mentorProgram */
                $mentorProgram = MentorProgram::query()->findOrFail($this->input('mentor_program_id'));
                $startDate = Date::createFromFormat(
                    '!Y-m-d H:i',
                    $this->input('fromDate').' '.$this->input('fromTime'), // @pest-mutate-ignore ConcatOperandRemoval
                    $timezone
                );

                $endDate = Date::createFromFormat(
                    '!Y-m-d H:i',
                    $this->input('toDate').' '.$this->input('toTime'),  // @pest-mutate-ignore ConcatOperandRemoval
                    $timezone
                );

                $sessionDuration = (int) ($this->integer('selectedDuration') ?: $mentorProgram->session_duration);

                $isValidSlot = new CheckBookingSlotService(
                    $startDate,
                    $endDate,
                    $mentorProgram,
                    $sessionDuration,
                )->isValidSlot();

                if (! $isValidSlot) {
                    $validator->errors()->add('fromTime', 'The selected time must be one of the available time slots.');
                }

                $isWithinAvailableSlots = new CheckTimeSlotReservedService(
                    $startDate,
                    $endDate,
                    $timezone,
                    auth()->user(),
                    $mentorProgram,
                )->isSlotAvailable();

                if (! $isWithinAvailableSlots) {
                    $validator->errors()->add('fromDate', 'there are another events on this time');
                }
            }
        });
    }
}
