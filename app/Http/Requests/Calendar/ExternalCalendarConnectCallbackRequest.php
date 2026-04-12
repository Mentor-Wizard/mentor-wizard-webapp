<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar;

use Illuminate\Contracts\Validation\Validator;

class ExternalCalendarConnectCallbackRequest extends ExternalCalendarRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->has('error')) {
            return [];
        }

        return [
            'code' => ['required', 'string'],
        ];
    }

    protected function failedValidation(Validator $validator): never
    {
        $provider = $this->resolveProvider();
        $this->cleanupIntegration($this->user(), $provider);

        parent::failedValidation($validator);
    }
}
