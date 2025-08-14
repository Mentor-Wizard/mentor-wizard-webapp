<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar;

use App\Enums\EventStatusEnum;
use App\Enums\EventTypeEnum;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Override;

class EditEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'         => ['required', 'string', 'max:255'],
            'fromDate'      => ['required', 'date', 'after_or_equal:today'],
            'toDate'        => ['required', 'date', 'after_or_equal:fromDate'],
            'fromTime'      => ['required', 'date_format:H:i'],
            'toTime'        => ['required', 'date_format:H:i', 'after:fromTime'],
            'description'   => ['max:2000'],
            'type'          => ['required', Rule::in(EventTypeEnum::values())],
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'title.required'            => 'Event title is required.',
            'title.max'                 => 'Event title cannot exceed 255 characters.',
            'fromDate.required'         => 'Start date is required.',
            'fromDate.date'             => 'Start date must be a valid date.',
            'fromDate.after_or_equal'   => 'Start date cannot be in the past.',
            'toDate.required'           => 'End date is required.',
            'toDate.date'               => 'End date must be a valid date.',
            'toDate.after_or_equal'     => 'End date must be on or after the start date.',
            'fromTime.required'         => 'Start time is required.',
            'fromTime.date_format'      => 'Start time must be in HH:MM format.',
            'toTime.required'           => 'End time is required.',
            'toTime.date_format'        => 'End time must be in HH:MM format.',
            'toTime.after'              => 'End time must be after start time.',
            'description.max'           => 'Description cannot exceed 2000 characters.',
            'type.required'             => 'Event type is required.',
            'type.in'                   => 'Event type must be either individual or group.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            if (! $this->checkAvailableSlots($this->input('fromDate'), $this->input('fromTime'), $this->input('toDate'), $this->input('toTime'))) {
                $validator->errors()->add('fromDate', 'there are another events on this time');
            }
        });
    }

    public function getEventData(): array
    {
        $validated = $this->validated();
        $startDateTime = Carbon::createFromFormat(
            'Y-m-d H:i',
            $validated['fromDate'].' '.$validated['fromTime']
        );
        $endDateTime = Carbon::createFromFormat(
            'Y-m-d H:i',
            $validated['toDate'].' '.$validated['toTime']
        );

        $duration = $startDateTime->diffInSeconds($endDateTime);
        $eventType = match ($validated['type']) {
            'individual'        => EventTypeEnum::INDIVIDUAL->value,
            'group'             => EventTypeEnum::GROUP->value,
            default             => EventTypeEnum::INDIVIDUAL->value,
        };

        return [
            'unique_id'         => (string) str()->uuid(),
            'title'             => $validated['title'],
            'start_date_time'   => $startDateTime,
            'end_date_time'     => $endDateTime,
            'duration'          => $duration,
            'type'              => $eventType,
            'description'       => $validated['description'],
            'status'            => EventStatusEnum::CONFIRMED,
            'date'              => $startDateTime->format('Y-m-d'),
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has(['fromDate', 'toDate', 'fromTime', 'toTime'])) {
            $fromDateTime = Carbon::createFromFormat('Y-m-d H:i', $this->fromDate.' '.$this->fromTime);
            $toDateTime = Carbon::createFromFormat('Y-m-d H:i', $this->toDate.' '.$this->toTime);
            if ($this->fromDate === $this->toDate && $this->toTime <= $this->fromTime) {
                return;
            }
        }
    }

    private function checkAvailableSlots(string $fromDate, string $fromTime, string $toDate, string $toTime)
    {
        $this->validated();
        $startDateTimestamp = Carbon::createFromFormat(
            'Y-m-d H:i',
            $fromDate.' '.$fromTime
        )->timestamp;
        $endDateTimestamp = Carbon::createFromFormat(
            'Y-m-d H:i',
            $toDate.' '.$toTime
        )->timestamp;

        return auth()->user()->checkAvailableSlots($startDateTimestamp, $endDateTimestamp);
    }
}
