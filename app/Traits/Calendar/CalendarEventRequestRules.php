<?php

declare(strict_types=1);

namespace App\Traits\Calendar;

use App\Enums\CalendarEventColoursEnum;
use App\Enums\CalendarEventTypeEnum;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use Override;

trait CalendarEventRequestRules
{
    /**
     * @return array<string, list<In|string>>
     */
    public function rules(): array
    {
        return [
            'title'                 => ['required', 'string', 'max:255'],
            'fromDate'              => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'toDate'                => ['required', 'date_format:Y-m-d', 'after_or_equal:fromDate'],
            'fromTime'              => ['required', 'date_format:H:i', 'bail'],
            'toTime'                => ['required', 'date_format:H:i', 'after:fromTime', 'bail'],
            'colour'                => ['required', Rule::in(CalendarEventColoursEnum::values())],
            'description'           => ['max:2000'],
            'webLink'               => ['sometimes', 'nullable', 'url'],
            'type'                  => ['required', Rule::in(CalendarEventTypeEnum::values())],
            'mentor_program_id'     => ['required', 'integer'],
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
            'colour.required'         => 'Colour is required.',
            'fromTime.date_format'    => 'Start time must be in HH:MM format.',
            'toTime.required'         => 'End time is required.',
            'toTime.date_format'      => 'End time must be in HH:MM format.',
            'toTime.after'            => 'End time must be after start time.',
            'description.max'         => 'Description cannot exceed 2000 characters.',
            'webLink.url'             => 'Weblink to event is not valid',
            'type.required'           => 'CalendarEvent type is required.',
            'type.in'                 => 'CalendarEvent type must be either individual or group.',
            'mentor_program_id'       => 'CalendarEvent should be related to mentor program.',
        ];
    }
}
