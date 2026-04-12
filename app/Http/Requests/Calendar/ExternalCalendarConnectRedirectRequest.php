<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Validator as ValidatorImpl;

class ExternalCalendarConnectRedirectRequest extends ExternalCalendarRequest
{
    public function rules(): array
    {
        $provider = $this->resolveProvider();

        if ($provider === null || $provider->usesAppCredentials()) {
            return [];
        }

        return [
            'client_id'     => ['required', 'string', 'min:10'],
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

            if ($provider->isCalDav()) {
                $validator->errors()->add('provider', 'This provider does not support OAuth redirect.');
            }
        });
    }

    protected function failedValidation(Validator $validator): never
    {
        $this->cleanupIntegration($this->user(), $this->resolveProvider());

        parent::failedValidation($validator);
    }
}
