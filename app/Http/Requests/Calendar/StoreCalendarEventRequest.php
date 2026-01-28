<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar;

use App\Models\CalendarEvent;
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
        if (! $this->user()->can('create', CalendarEvent::class)) {
            return false;
        }

        $mentorProgramId = $this->input('mentor_program_id');
        $mentorProgram = MentorProgram::query()->find($mentorProgramId);

        if ($mentorProgram === null) {
            return true;
        }

        assert($mentorProgram instanceof MentorProgram);

        $user = auth()->user();
        $isMentorOfProgram = $mentorProgram->mentor_id === $user->getKey();
        $isMentor = $user->hasRole('mentor');

        return $isMentorOfProgram || ! $isMentor;
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
