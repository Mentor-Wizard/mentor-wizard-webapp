<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar;

use App\Enums\CalendarEventColoursEnum;
use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Services\Calendar\CheckTimeSlotReservedService;
use Exception;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Rule;
use Override;

class EditCalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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
            'timezone'    => ['required', 'string'],
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

            $isWithinAvailableSlots = new CheckTimeSlotReservedService(
                $this->input('fromDate'),
                $this->input('fromTime'),
                $this->input('toDate'),
                $this->input('toTime'),
                $this->input('timezone', 'UTC'),
                auth()->user(), [$this->input('id')])->isSlotAvailable();

            if (! $isWithinAvailableSlots) {
                $validator->errors()->add('fromDate', 'there are another events on this time');
            }
        });
    }

    public function getEventData(): array
    {
        $validated = $this->validated();

        $startDateTime = Date::createFromFormat(
            'Y-m-d H:i',
            $validated['fromDate'].' '.$validated['fromTime'],
            $validated['timezone']
        )?->setTimezone('UTC');
        $endDateTime = Date::createFromFormat(
            'Y-m-d H:i',
            $validated['toDate'].' '.$validated['toTime'],
            $validated['timezone']
        )?->setTimezone('UTC');

        $duration = $startDateTime?->diffInSeconds($endDateTime);
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

    protected function prepareForValidation(): void
    {
        if ($this->has(['fromDate', 'toDate', 'fromTime', 'toTime'])) {
            $fromDateTime = Date::createFromFormat('Y-m-d H:i', $this->fromDate.' '.$this->fromTime);
            $toDateTime = Date::createFromFormat('Y-m-d H:i', $this->toDate.' '.$this->toTime);
            if ($this->fromDate === $this->toDate && $this->toTime <= $this->fromTime) {
                return;
            }
        }
    }
}
