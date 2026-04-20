<?php

declare(strict_types=1);

namespace App\Services\ExternalCalendar;

use App\Enums\CalendarProviderEnum;
use App\Models\ExternalCalendarEvent;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use App\Services\ExternalCalendar\Contracts\ExternalCalendarServiceInterface;
use App\Services\ExternalCalendar\Contracts\OAuthCalendarServiceInterface;
use Illuminate\Support\Facades\DB;
use LogicException;

class ExternalCalendarSynchronizationService
{
    public function __construct(
        private readonly ExternalCalendarServiceFactory $factory,
    ) {}

    public function saveCredentialsAndBuildOAuthUrl(
        User $user,
        CalendarProviderEnum $provider,
        ?string $clientId,
        ?string $clientSecret,
    ): string {
        $service = $this->resolveOAuthService($provider);
        $service->saveCredentials($user, $clientId, $clientSecret);

        $state = encrypt(json_encode(['user_id' => $user->getKey(), 'provider' => $provider->value]));

        return $service->buildOAuthUrl($clientId, $state);
    }

    public function saveCredentials(
        User $user,
        CalendarProviderEnum $provider,
        ?string $clientId,
        ?string $clientSecret,
    ): void {
        $this->resolveService($provider)->saveCredentials($user, $clientId, $clientSecret);
    }

    public function handleCallback(User $user, CalendarProviderEnum $provider, string $code): UserCalendarIntegration
    {
        return $this->resolveOAuthService($provider)->handleCallback($user, $code);
    }

    /**
     * @return array{success: bool, calendars: list<array{id: string, name: string, primary: bool}>, error: string|null}
     */
    public function fetchCalendars(User $user, CalendarProviderEnum $provider): array
    {
        $integration = UserCalendarIntegration::query()
            ->where('user_id', $user->getKey())
            ->where('provider', $provider)
            ->firstOrFail();

        return $this->resolveService($provider)->fetchCalendars($integration);
    }

    public function selectCalendar(User $user, CalendarProviderEnum $provider, string $calendarId, string $calendarName): UserCalendarIntegration
    {
        return $this->resolveService($provider)->selectCalendar($user, $calendarId, $calendarName);
    }

    public function disconnect(User $user, CalendarProviderEnum $provider): void
    {
        DB::transaction(function () use ($user, $provider): void {
            ExternalCalendarEvent::query()
                ->where('user_id', $user->getKey())
                ->where('provider', $provider)
                ->delete();

            UserCalendarIntegration::query()
                ->where('user_id', $user->getKey())
                ->where('provider', $provider)
                ->delete();
        });
    }

    private function resolveOAuthService(CalendarProviderEnum $provider): OAuthCalendarServiceInterface
    {
        $service = $this->resolveService($provider);

        if (! $service instanceof OAuthCalendarServiceInterface) {
            throw new LogicException('Provider ['.$provider->value.'] does not support OAuth.');
        }

        return $service;
    }

    private function resolveService(CalendarProviderEnum $provider): ExternalCalendarServiceInterface
    {
        return $this->factory->for($provider);
    }
}
