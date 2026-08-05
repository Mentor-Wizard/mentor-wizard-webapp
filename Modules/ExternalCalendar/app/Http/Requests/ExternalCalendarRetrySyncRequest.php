<?php

declare(strict_types=1);

namespace Modules\ExternalCalendar\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Modules\ExternalCalendar\Enums\CalendarProviderEnum;

class ExternalCalendarRetrySyncRequest extends ExternalCalendarRequest
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
}
