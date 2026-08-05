<?php

declare(strict_types=1);

namespace Modules\ExternalCalendar\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Modules\ExternalCalendar\Enums\CalendarProviderEnum;
use Override;

class ExternalCalendarConnectDirectRequest extends ExternalCalendarRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'client_id'     => ['required', 'string', 'email'],
            'client_secret' => ['required', 'string', 'min:10'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $provider = $this->resolveProvider();

            if (! $provider instanceof CalendarProviderEnum) {
                $validator->errors()->add('provider', 'Invalid calendar provider.');

                return;
            }

            if (! $provider->isCalDav()) {
                $validator->errors()->add('provider', 'This provider does not support direct credentials.');
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
