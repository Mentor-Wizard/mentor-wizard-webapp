<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar;

use Illuminate\Validation\Validator as ValidatorImpl;

class ExternalCalendarRetrySyncRequest extends ExternalCalendarRequest
{
    public function rules(): array
    {
        return [];
    }

    public function withValidator(ValidatorImpl $validator): void
    {
        $validator->after(function (ValidatorImpl $validator): void {
            if ($this->resolveProvider() === null) {
                $validator->errors()->add('provider', 'Invalid calendar provider.');
            }
        });
    }
}
