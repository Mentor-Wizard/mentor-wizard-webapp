<?php

declare(strict_types=1);

use App\Actions\Calendar\ExternalCalendarConnectCallback;
use App\Enums\CalendarProviderEnum;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use App\Services\ExternalCalendar\ExternalCalendarSynchronizationService;
use Database\Seeders\RoleSeeder;

mutates(ExternalCalendarConnectCallback::class);

describe('ExternalCalendarConnectCallback', function (): void {
    beforeEach(function (): void {
        config(['calendar.encryption_key1' => base64_encode(random_bytes(32))]);
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
    });

    it('redirects with error when OAuth error param is present', function (): void {
        $response = $this->get(
            route('external-calendar.connect.callback', ['provider' => 'google']).'?error=access_denied',
        );

        $response->assertRedirect(route('profile.edit'));

        expect(session('error'))->toBe('Authorization was denied or cancelled.');
    });

    it('redirects with error when state is missing and no session fallback', function (): void {
        $syncService = Mockery::mock(ExternalCalendarSynchronizationService::class);
        app()->instance(ExternalCalendarSynchronizationService::class, $syncService);

        $response = $this->get(
            route('external-calendar.connect.callback', ['provider' => 'google']).'?code=auth-code',
        );

        $response->assertRedirect(route('profile.edit'));

        expect(session('error'))->toBe('Authorization session expired or invalid. Please try again.');
    });

    it('processes callback and redirects with calendars when state is valid', function (): void {
        $state = encrypt(json_encode(['user_id' => $this->user->getKey(), 'provider' => CalendarProviderEnum::Google->value]));

        $integration = UserCalendarIntegration::factory()->create([
            'user_id'  => $this->user->getKey(),
            'provider' => CalendarProviderEnum::Google,
        ]);

        $syncService = Mockery::mock(ExternalCalendarSynchronizationService::class);
        $syncService->shouldReceive('handleCallback')
            ->once()
            ->with(
                Mockery::on(fn ($u): bool => $u->getKey() === $this->user->getKey()),
                CalendarProviderEnum::Google,
                'auth-code-123',
            )
            ->andReturn($integration);

        $syncService->shouldReceive('fetchCalendars')
            ->once()
            ->with(
                Mockery::on(fn ($u): bool => $u->getKey() === $this->user->getKey()),
                CalendarProviderEnum::Google,
            )
            ->andReturn([
                'success'   => true,
                'calendars' => [['id' => 'cal-1', 'name' => 'Primary', 'primary' => true]],
                'error'     => null,
            ]);

        app()->instance(ExternalCalendarSynchronizationService::class, $syncService);

        $response = $this->get(
            route('external-calendar.connect.callback', ['provider' => 'google'])
            .'?code=auth-code-123&state='.urlencode($state),
        );

        $response->assertRedirect(route('profile.edit'));
        expect(session('calendar_provider'))->toBe(CalendarProviderEnum::Google->value)
            ->and(session('calendars'))->toHaveCount(1);
    });

    it('falls back to session state when query state is absent', function (): void {
        $integration = UserCalendarIntegration::factory()->create([
            'user_id'  => $this->user->getKey(),
            'provider' => CalendarProviderEnum::Outlook,
        ]);

        $syncService = Mockery::mock(ExternalCalendarSynchronizationService::class);
        $syncService->shouldReceive('handleCallback')
            ->once()
            ->andReturn($integration);

        $syncService->shouldReceive('fetchCalendars')
            ->once()
            ->andReturn([
                'success'   => true,
                'calendars' => [['id' => 'cal-1', 'name' => 'Work', 'primary' => false]],
                'error'     => null,
            ]);

        app()->instance(ExternalCalendarSynchronizationService::class, $syncService);

        $response = $this->withSession([
            'calendar_oauth_pending' => [
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::Outlook->value,
            ],
        ])->get(
            route('external-calendar.connect.callback', ['provider' => 'outlook']).'?code=auth-code-xyz',
        );

        $response->assertRedirect(route('profile.edit'));

        expect(session('calendar_provider'))->toBe(CalendarProviderEnum::Outlook->value);
    });

    it('redirects with error when fetchCalendars returns empty calendars', function (): void {
        $state = encrypt(json_encode(['user_id' => $this->user->getKey(), 'provider' => CalendarProviderEnum::Google->value]));

        $integration = UserCalendarIntegration::factory()->create([
            'user_id'  => $this->user->getKey(),
            'provider' => CalendarProviderEnum::Google,
        ]);

        $syncService = Mockery::mock(ExternalCalendarSynchronizationService::class);
        $syncService->shouldReceive('handleCallback')->once()->andReturn($integration);
        $syncService->shouldReceive('fetchCalendars')
            ->once()
            ->andReturn([
                'success'   => true,
                'calendars' => [],
                'error'     => null,
            ]);

        app()->instance(ExternalCalendarSynchronizationService::class, $syncService);

        $response = $this->get(
            route('external-calendar.connect.callback', ['provider' => 'google'])
            .'?code=code&state='.urlencode($state),
        );

        $response->assertRedirect(route('profile.edit'));

        expect(session('error'))->toBe('No calendars found on this account.');
    });

    it('redirects with service error message when fetchCalendars fails', function (): void {
        $state = encrypt(json_encode(['user_id' => $this->user->getKey(), 'provider' => CalendarProviderEnum::Google->value]));

        $integration = UserCalendarIntegration::factory()->create([
            'user_id'  => $this->user->getKey(),
            'provider' => CalendarProviderEnum::Google,
        ]);

        $syncService = Mockery::mock(ExternalCalendarSynchronizationService::class);
        $syncService->shouldReceive('handleCallback')->once()->andReturn($integration);
        $syncService->shouldReceive('fetchCalendars')
            ->once()
            ->andReturn([
                'success'   => false,
                'calendars' => [],
                'error'     => 'Token exchange failed.',
            ]);

        app()->instance(ExternalCalendarSynchronizationService::class, $syncService);

        $response = $this->get(
            route('external-calendar.connect.callback', ['provider' => 'google'])
            .'?code=code&state='.urlencode($state),
        );

        $response->assertRedirect(route('profile.edit'));

        expect(session('error'))->toBe('Token exchange failed.');
    });

    it('redirects with error when state cannot be decrypted and no session fallback', function (): void {
        $syncService = Mockery::mock(ExternalCalendarSynchronizationService::class);
        app()->instance(ExternalCalendarSynchronizationService::class, $syncService);

        $response = $this->get(
            route('external-calendar.connect.callback', ['provider' => 'google'])
            .'?code=code&state=not-a-valid-encrypted-payload',
        );

        $response->assertRedirect(route('profile.edit'));

        expect(session('error'))->toBe('Authorization session expired or invalid. Please try again.');
    });
});
