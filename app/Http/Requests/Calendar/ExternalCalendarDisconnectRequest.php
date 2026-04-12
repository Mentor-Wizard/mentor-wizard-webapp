<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Validator as ValidatorImpl;

class ExternalCalendarDisconnectRequest extends ExternalCalendarRequest
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

    protected function failedValidation(Validator $validator): never
    {
        $this->cleanupIntegration($this->user(), $this->resolveProvider());

        parent::failedValidation($validator);
    }
}
