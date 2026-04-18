<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar;

use Illuminate\Contracts\Validation\Validator;
use Override;

class ExternalCalendarConnectCallbackRequest extends ExternalCalendarRequest
{
    #[Override]
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        if ($this->has('error')) {
            return [];
        }

        return [
            'code' => ['required', 'string'],
        ];
    }

    #[Override]
    protected function failedValidation(Validator $validator): never
    {
        $provider = $this->resolveProvider();
        $this->cleanupIntegration($this->user(), $provider);

        parent::failedValidation($validator);
    }
}
