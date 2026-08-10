<?php

declare(strict_types=1);

namespace Modules\ExternalCalendar\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\ExternalCalendar\Enums\CalendarProviderEnum;
use Modules\ExternalCalendar\Traits\HandlesCalendarIntegrationCleanup;
use Override;

abstract class ExternalCalendarRequest extends FormRequest
{
    use HandlesCalendarIntegrationCleanup;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function resolveProvider(): ?CalendarProviderEnum
    {
        return CalendarProviderEnum::tryFrom((string) $this->route('provider'));
    }

    #[Override]
    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(
            to_route('profile.edit')->with('error', $validator->errors()->first()),
        );
    }

    protected function failedAuthorization(): never
    {
        throw new HttpResponseException(
            to_route('profile.edit')->with('error', 'Unauthorized action.'),
        );
    }
}
