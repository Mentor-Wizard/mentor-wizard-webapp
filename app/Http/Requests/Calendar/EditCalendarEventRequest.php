<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar;

use App\Enums\CalendarEventColoursEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Services\Calendar\CheckTimeSlotReservedService;
use Exception;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use Override;

class EditCalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, ValidationRule|string|In>>
     */
    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'fromDate'    => ['required', 'date', 'after_or_equal:today'],
            'toDate'      => ['required', 'date', 'after_or_equal:fromDate'],
            'fromTime'    => ['required', 'date_format:H:i'],
            'toTime'      => ['required', 'date_format:H:i', 'after:fromTime'],
            'colour'      => ['required', Rule::in(CalendarEventColoursEnum::values())],
            'description' => ['max:2000'],
            'type'        => ['required', Rule::in(CalendarEventTypeEnum::values())],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function messages(): array
    {
        return [
            'title.required'          => 'CalendarEvent title is required.',
            'title.max'               => 'CalendarEvent title cannot exceed 255 characters.',
            'fromDate.required'       => 'Start date is required.',
            'fromDate.date'           => 'Start date must be a valid date.',
            'fromDate.after_or_equal' => 'Start date cannot be in the past.',
            'toDate.required'         => 'End date is required.',
            'toDate.date'             => 'End date must be a valid date.',
            'toDate.after_or_equal'   => 'End date must be on or after the start date.',
            'fromTime.required'       => 'Start time is required.',
            'fromTime.date_format'    => 'Start time must be in HH:MM format.',
            'toTime.required'         => 'End time is required.',
            'toTime.date_format'      => 'End time must be in HH:MM format.',
            'toTime.after'            => 'End time must be after start time.',
            'description.max'         => 'Description cannot exceed 2000 characters.',
            'type.required'           => 'CalendarEvent type is required.',
            'type.in'                 => 'CalendarEvent type must be either individual or group.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            try {
                Date::parse($this->input('fromDate'));
                Date::parse($this->input('fromDate').' '.$this->input('fromTime'));
            } catch (Exception) {
                $validator->errors()->add('fromDate', 'fromDate is not valid');
            }

            try {
                Date::parse($this->input('toDate'));
                Date::parse($this->input('toDate').' '.$this->input('toTime'));
            } catch (Exception) {
                $validator->errors()->add('fromDate', 'toDate is not valid');
            }

            // Get timezone from user profile
            $user = auth()->user();
            $profile = $user?->profile;
            $timezone = $profile ? $profile->timezone : config('app.timezone');

            $isWithinAvailableSlots = new CheckTimeSlotReservedService(
                $this->input('fromDate'),
                $this->input('fromTime'),
                $this->input('toDate'),
                $this->input('toTime'),
                $timezone,
                auth()->user(), [$this->input('id')])->isSlotAvailable();

            if (! $isWithinAvailableSlots) {
                $validator->errors()->add('fromDate', 'there are another events on this time');
            }
        });
    }
}
