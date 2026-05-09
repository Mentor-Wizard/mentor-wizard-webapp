<?php

declare(strict_types=1);

namespace App\DTO\ExternalCalendar;

use Carbon\CarbonImmutable;

/**
 * Represents a calendar event fetched from an external calendar provider.
 * All times are normalised to UTC regardless of the provider's native storage format.
 */
readonly class ExternalCalendarEventData
{
    public function __construct(
        /** Provider-specific event identifier (string ID, CalDAV URL, etc.) */
        public string $externalId,

        /** Event title / summary */
        public string $title,

        /** Start time in UTC */
        public CarbonImmutable $startUtc,

        /** End time in UTC */
        public CarbonImmutable $endUtc,

        /** Optional plain-text description */
        public ?string $description,

        /**
         * IANA timezone identifier as reported by the provider (e.g. "Europe/Kyiv").
         * When the provider stores the event in UTC this will be "UTC".
         */
        public string $providerTimezone,
    ) {}
}
