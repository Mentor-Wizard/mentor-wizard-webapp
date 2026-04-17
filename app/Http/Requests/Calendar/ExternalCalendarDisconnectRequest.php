<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar;

use App\Enums\CalendarProviderEnum;
use Illuminate\Contracts\Validation\Validator;
use Override;

class ExternalCalendarDisconnectRequest extends ExternalCalendarRequest
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

    #[Override]
    protected function failedValidation(Validator $validator): never
    {
        $this->cleanupIntegration($this->user(), $this->resolveProvider());

        parent::failedValidation($validator);
    }
}
