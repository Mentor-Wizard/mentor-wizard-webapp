<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar\CalendarEvent;

use App\Models\CalendarEvent;
use Illuminate\Foundation\Http\FormRequest;

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
