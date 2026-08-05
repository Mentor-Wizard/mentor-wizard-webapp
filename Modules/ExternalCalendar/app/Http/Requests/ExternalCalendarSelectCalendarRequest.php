<?php

declare(strict_types=1);

namespace Modules\ExternalCalendar\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Modules\ExternalCalendar\Enums\CalendarProviderEnum;

class ExternalCalendarSelectCalendarRequest extends ExternalCalendarRequest
{
    private const string CALDAV_HOST_SUFFIX = 'icloud.com';

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'calendar_id'   => ['required', 'string'],
            'calendar_name' => ['required', 'string'],
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

            if ($provider->isCalDav() && ! $this->isAllowedCalDavCalendarUrl($this->string('calendar_id')->toString())) {
                $validator->errors()->add('calendar_id', 'Invalid calendar identifier for this provider.');
            }
        });
    }

    /**
     * For CalDAV providers `calendar_id` is stored verbatim and later used as the
     * target URL of server-side PROPFIND/REPORT/PUT/DELETE requests, so an
     * unrestricted value would let an authenticated user point the application at
     * arbitrary internal hosts. Only absolute HTTPS URLs on the provider's own
     * domain are accepted.
     */
    private function isAllowedCalDavCalendarUrl(string $calendarId): bool
    {
        $parts = parse_url($calendarId);

        if (! is_array($parts) || ($parts['scheme'] ?? null) !== 'https' || ! isset($parts['host'])) {
            return false;
        }

        $host = mb_strtolower($parts['host']);

        return $host === self::CALDAV_HOST_SUFFIX || str_ends_with($host, '.'.self::CALDAV_HOST_SUFFIX);
    }
}
