<?php

declare(strict_types=1);

namespace App\Http\Requests\Calendar;

use App\Enums\CalendarProviderEnum;
use App\Traits\Calendar\HandlesCalendarIntegrationCleanup;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class ExternalCalendarRequest extends FormRequest
{
    use HandlesCalendarIntegrationCleanup;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

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

    public function resolveProvider(): ?CalendarProviderEnum
    {
        return CalendarProviderEnum::tryFrom((string) $this->route('provider'));
    }
}
