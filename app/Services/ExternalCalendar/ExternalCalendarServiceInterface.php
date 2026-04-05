<?php

declare(strict_types=1);

namespace App\Services\ExternalCalendar;

use App\Models\CalendarEvent;
use App\Models\User;
use App\Models\UserCalendarIntegration;

interface ExternalCalendarServiceInterface
{
    public function saveCredentials(User $user, string $clientId, string $clientSecret): UserCalendarIntegration;

    public function buildOAuthUrl(string $clientId, string $state): string;

    public function handleCallback(User $user, string $code): UserCalendarIntegration;

    public function selectCalendar(User $user, string $calendarId, string $calendarName): UserCalendarIntegration;

    /**
     * @return array{success: bool, calendars: list<array{id: string, name: string, primary: bool}>, error: string|null}
     */
    public function fetchCalendars(string $accessToken): array;

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
