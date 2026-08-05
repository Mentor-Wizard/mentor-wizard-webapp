<?php

declare(strict_types=1);

namespace Modules\Calendar\Http\Requests\CalendarEvent;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Calendar\Models\CalendarEvent;

class ConfirmCalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        $calendarEvent = $this->route('calendarEvent');

        if (! $calendarEvent instanceof CalendarEvent) {
            return false;
        }

        return $this->user()->can('confirm', $calendarEvent);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
