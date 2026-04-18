<?php

declare(strict_types=1);

use App\Actions\Calendar\ExternalCalendar\ExternalCalendarConnectCallback;
use App\Enums\CalendarProviderEnum;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use App\Services\ExternalCalendar\ExternalCalendarSynchronizationService;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\get;

mutates(ExternalCalendarConnectCallback::class);

describe('ExternalCalendarConnectCallback', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        config(['calendar.encryption_key1' => base64_encode(random_bytes(32))]);

        $this->user = User::factory()->create();
    });

    describe('handle — error param', function (): void {
        it('redirects to profile calendars tab with an error when OAuth denies access', function (): void {
            $response = get(
                route('external-calendar.connect.callback', ['provider' => 'google']).'?error=access_denied',
            );

            $response->assertRedirect(route('profile.edit', ['tab' => 'calendars']));
            $response->assertSessionHas('error', 'Authorization was denied or cancelled.');
        });

        it('cleans up the integration record when error param is present', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::Google,
            ]);

            $state = encrypt(json_encode(['user_id' => $this->user->getKey(), 'provider' => 'google']));

            get(
                route('external-calendar.connect.callback', ['provider' => 'google'])
                    .'?error=access_denied&state='.urlencode($state),
            );

            $this->assertDatabaseMissing(UserCalendarIntegration::class, [
                'id' => $integration->getKey(),
            ]);
        });
    });

    describe('handle — invalid/missing state', function (): void {
        it('redirects with session-expired error when state is empty and session has no pending data', function (): void {
            $response = get(
                route('external-calendar.connect.callback', ['provider' => 'google']).'?code=auth-code',
            );

            $response->assertRedirect(route('profile.edit', ['tab' => 'calendars']));
            $response->assertSessionHas('error', 'Authorization session expired or invalid. Please try again.');
        });

        it('redirects with session-expired error when state cannot be decrypted', function (): void {
            $response = get(
                route('external-calendar.connect.callback', ['provider' => 'google']).'?code=auth-code&state=bad-state',
            );

            $response->assertRedirect(route('profile.edit', ['tab' => 'calendars']));
            $response->assertSessionHas('error', 'Authorization session expired or invalid. Please try again.');
        });
    });

    describe('handle — state from URL', function (): void {
        it('calls handleCallback and fetchCalendars with the resolved state and returns calendars', function (): void {
            $state = encrypt(json_encode(['user_id' => $this->user->getKey(), 'provider' => 'google']));

            $service = $this->mock(ExternalCalendarSynchronizationService::class);
            $service->shouldReceive('handleCallback')
                ->once()
                ->with(
                    Mockery::on(fn (User $u): bool => $u->getKey() === $this->user->getKey()),
                    CalendarProviderEnum::Google,
                    'auth-code',
                )
                ->andReturn(UserCalendarIntegration::factory()->create([
                    'user_id'  => $this->user->getKey(),
                    'provider' => CalendarProviderEnum::Google,
                ]));

            $service->shouldReceive('fetchCalendars')
                ->once()
                ->andReturn([
                    'success'   => true,
                    'calendars' => [['id' => 'primary', 'name' => 'Primary', 'primary' => true]],
                    'error'     => null,
                ]);

            $response = get(
                route('external-calendar.connect.callback', ['provider' => 'google'])
                    .'?code=auth-code&state='.urlencode($state),
            );

            $response->assertRedirect(route('profile.edit', ['tab' => 'calendars']));
            $response->assertSessionHas('calendar_provider', 'google');
            $response->assertSessionHas('calendars');
        });

        it('redirects with error when handleCallback throws an exception', function (): void {
            $state = encrypt(json_encode(['user_id' => $this->user->getKey(), 'provider' => 'google']));

            $service = $this->mock(ExternalCalendarSynchronizationService::class);
            $service->shouldReceive('handleCallback')
                ->once()
                ->andThrow(new RuntimeException('Token exchange failed'));

            $response = get(
                route('external-calendar.connect.callback', ['provider' => 'google'])
                    .'?code=auth-code&state='.urlencode($state),
            );

            $response->assertRedirect(route('profile.edit', ['tab' => 'calendars']));
            $response->assertSessionHas('error', 'Failed to complete calendar authorization. Please try again.');
        });

        it('redirects with error when fetchCalendars returns no calendars', function (): void {
            $state = encrypt(json_encode(['user_id' => $this->user->getKey(), 'provider' => 'google']));

            $service = $this->mock(ExternalCalendarSynchronizationService::class);
            $service->shouldReceive('handleCallback')->once()->andReturn(
                UserCalendarIntegration::factory()->create([
                    'user_id'  => $this->user->getKey(),
                    'provider' => CalendarProviderEnum::Google,
                ]),
            );
            $service->shouldReceive('fetchCalendars')
                ->once()
                ->andReturn(['success' => true, 'calendars' => [], 'error' => null]);

            $response = get(
                route('external-calendar.connect.callback', ['provider' => 'google'])
                    .'?code=auth-code&state='.urlencode($state),
            );

            $response->assertRedirect(route('profile.edit', ['tab' => 'calendars']));
            $response->assertSessionHas('error', 'No calendars found on this account.');
        });

        it('redirects with error when fetchCalendars reports failure', function (): void {
            $state = encrypt(json_encode(['user_id' => $this->user->getKey(), 'provider' => 'google']));

            $service = $this->mock(ExternalCalendarSynchronizationService::class);
            $service->shouldReceive('handleCallback')->once()->andReturn(
                UserCalendarIntegration::factory()->create([
                    'user_id'  => $this->user->getKey(),
                    'provider' => CalendarProviderEnum::Google,
                ]),
            );
            $service->shouldReceive('fetchCalendars')
                ->once()
                ->andReturn(['success' => false, 'calendars' => [], 'error' => 'API error']);

            $response = get(
                route('external-calendar.connect.callback', ['provider' => 'google'])
                    .'?code=auth-code&state='.urlencode($state),
            );

            $response->assertSessionHas('error', 'API error');
        });
    });

    describe('handle — state from session', function (): void {
        it('resolves user and provider from session when state query param is absent', function (): void {
            $service = $this->mock(ExternalCalendarSynchronizationService::class);
            $service->shouldReceive('handleCallback')->once()->andReturn(
                UserCalendarIntegration::factory()->create([
                    'user_id'  => $this->user->getKey(),
                    'provider' => CalendarProviderEnum::Google,
                ]),
            );
            $service->shouldReceive('fetchCalendars')
                ->once()
                ->andReturn([
                    'success'   => true,
                    'calendars' => [['id' => 'primary', 'name' => 'Primary', 'primary' => true]],
                    'error'     => null,
                ]);

            $response = $this->withSession([
                'calendar_oauth_pending' => [
                    'user_id'  => $this->user->getKey(),
                    'provider' => 'google',
                ],
            ])->get(
                route('external-calendar.connect.callback', ['provider' => 'google']).'?code=auth-code',
            );

            $response->assertRedirect(route('profile.edit', ['tab' => 'calendars']));
            $response->assertSessionHas('calendar_provider', 'google');
        });

        it('redirects with error when session has no pending oauth data and state is absent', function (): void {
            $response = $this->withSession([])->get(
                route('external-calendar.connect.callback', ['provider' => 'google']).'?code=auth-code',
            );

            $response->assertSessionHas('error', 'Authorization session expired or invalid. Please try again.');
        });
    });
});
