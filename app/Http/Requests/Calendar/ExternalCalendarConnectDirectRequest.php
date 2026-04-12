<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Validator as ValidatorImpl;

class ExternalCalendarConnectDirectRequest extends ExternalCalendarRequest
{
    public function rules(): array
    {
        return [
            'client_id'     => ['required', 'string', 'email'],
            'client_secret' => ['required', 'string', 'min:10'],
        ];
    }

    public function withValidator(ValidatorImpl $validator): void
    {
        $validator->after(function (ValidatorImpl $validator): void {
            $provider = $this->resolveProvider();

            if ($provider === null) {
                $validator->errors()->add('provider', 'Invalid calendar provider.');

                return;
            }

            if (! $provider->isCalDav()) {
                $validator->errors()->add('provider', 'This provider does not support direct credentials.');
            }
        });
    }

    protected function failedValidation(Validator $validator): never
    {
        $this->cleanupIntegration($this->user(), $this->resolveProvider());

        parent::failedValidation($validator);
    }
}
