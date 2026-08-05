<?php

declare(strict_types=1);

namespace Modules\ExternalCalendar\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Modules\ExternalCalendar\Enums\CalendarProviderEnum;
use Override;

class ExternalCalendarConnectRedirectRequest extends ExternalCalendarRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $provider = $this->resolveProvider();

        if (! $provider instanceof CalendarProviderEnum || $provider->usesAppCredentials()) {
            return [];
        }

        return [
            'client_id'     => ['required', 'string', 'min:10'],
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

            if ($provider->isCalDav()) {
                $validator->errors()->add('provider', 'This provider does not support OAuth redirect.');
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
