<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar;

use App\Enums\CalendarProviderEnum;
use Illuminate\Contracts\Validation\Validator;

class ExternalCalendarRetrySyncRequest extends ExternalCalendarRequest
{
    /**
     * @return array{}
     */
    public function rules(): array
    {
        return [];
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
