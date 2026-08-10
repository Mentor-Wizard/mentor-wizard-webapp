<?php

declare(strict_types=1);

namespace Modules\ExternalCalendar\Services\Contracts;

use App\Models\User;
use DateTimeInterface;
use Modules\Calendar\Models\CalendarEvent;
use Modules\ExternalCalendar\DTO\ExternalCalendarEventData;
use Modules\ExternalCalendar\Models\UserCalendarIntegration;

interface ExternalCalendarServiceInterface
{
    public function saveCredentials(User $user, ?string $clientId, ?string $clientSecret): UserCalendarIntegration;

    public function selectCalendar(User $user, string $calendarId, string $calendarName): UserCalendarIntegration;

    /**
     * @return array{success: bool, calendars: list<array{id: string, name: string, primary: bool}>, error: string|null}
     */
    public function fetchCalendars(UserCalendarIntegration $integration): array;

    /**
     * Fetches events from the external calendar within the given UTC time range.
     *
     * @return list<ExternalCalendarEventData>
     */
    public function fetchEvents(UserCalendarIntegration $integration, DateTimeInterface $from, DateTimeInterface $to): array;

    /**
     * Creates an event in the external calendar.
     * Returns the external event ID assigned by the provider.
     */
    public function createEvent(CalendarEvent $event, UserCalendarIntegration $integration): string;

    /**
     * Updates an existing event in the external calendar.
     */
    public function updateEvent(CalendarEvent $event, UserCalendarIntegration $integration, string $externalEventId): void;

    /**
     * Deletes an event from the external calendar.
     */
    public function deleteEvent(UserCalendarIntegration $integration, string $externalEventId): void;
}
