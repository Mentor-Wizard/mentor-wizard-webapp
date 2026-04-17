<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar;

use App\Enums\CalendarProviderEnum;
use Illuminate\Contracts\Validation\Validator;

class ExternalCalendarSelectCalendarRequest extends ExternalCalendarRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'calendar_id'   => ['required', 'string'],
            'calendar_name' => ['required', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->resolveProvider() instanceof CalendarProviderEnum) {
                $validator->errors()->add('provider', 'Invalid calendar provider.');
            }
        });
    }
}
