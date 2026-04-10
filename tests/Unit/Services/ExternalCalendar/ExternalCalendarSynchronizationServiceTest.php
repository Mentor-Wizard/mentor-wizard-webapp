<?php

declare(strict_types=1);

use App\Enums\CalendarProviderEnum;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use App\Services\ExternalCalendar\ExternalCalendarServiceInterface;
use App\Services\ExternalCalendar\ExternalCalendarSynchronizationService;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

mutates(ExternalCalendarSynchronizationService::class);

describe('ExternalCalendarSynchronizationService', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        $this->service = new ExternalCalendarSynchronizationService;
    });

    describe('saveCredentialsAndBuildOAuthUrl', function (): void {
        it('saves credentials and returns an OAuth URL', function (): void {
            $mockService = Mockery::mock(ExternalCalendarServiceInterface::class);
            $mockService->shouldReceive('saveCredentials')
                ->once()
                ->with(
                    Mockery::on(fn ($u): bool => $u->getKey() === $this->user->getKey()),
                    'client-id',
                    'client-secret',
                );
            $mockService->shouldReceive('buildOAuthUrl')
                ->once()
                ->with('client-id', Mockery::type('string'))
                ->andReturn('https://accounts.google.com/o/oauth2/auth?state=encrypted');

            app()->instance(CalendarProviderEnum::GooglePersonalApp->getService(), $mockService);

            $url = $this->service->saveCredentialsAndBuildOAuthUrl(
                $this->user,
                CalendarProviderEnum::GooglePersonalApp,
                'client-id',
                'client-secret',
            );

            expect($url)->toBe('https://accounts.google.com/o/oauth2/auth?state=encrypted');
        });
    });

    describe('saveCredentials', function (): void {
        it('delegates to the resolved service', function (): void {
            $mockService = Mockery::mock(ExternalCalendarServiceInterface::class);
            $mockService->shouldReceive('saveCredentials')
                ->once()
                ->with(
                    Mockery::on(fn ($u): bool => $u->getKey() === $this->user->getKey()),
                    'apple-id',
                    'app-password',
                );

            app()->instance(CalendarProviderEnum::Apple->getService(), $mockService);

            $this->service->saveCredentials(
                $this->user,
                CalendarProviderEnum::Apple,
                'apple-id',
                'app-password',
            );
        });
    });

    describe('handleCallback', function (): void {
        it('delegates callback handling to the resolved service', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::Google,
            ]);

            $mockService = Mockery::mock(ExternalCalendarServiceInterface::class);
            $mockService->shouldReceive('handleCallback')
                ->once()
                ->with(
                    Mockery::on(fn ($u): bool => $u->getKey() === $this->user->getKey()),
                    'auth-code-123',
                )
                ->andReturn($integration);

            app()->instance(CalendarProviderEnum::Google->getService(), $mockService);

            $result = $this->service->handleCallback(
                $this->user,
                CalendarProviderEnum::Google,
                'auth-code-123',
            );

            expect($result->getKey())->toBe($integration->getKey());
        });
    });

    describe('fetchCalendars', function (): void {
        it('fetches calendars for an existing integration', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::Google,
            ]);

            $mockService = Mockery::mock(ExternalCalendarServiceInterface::class);
            $mockService->shouldReceive('fetchCalendars')
                ->once()
                ->with(Mockery::on(fn ($i): bool => $i->getKey() === $integration->getKey()))
                ->andReturn([
                    'success'   => true,
                    'calendars' => [
                        ['id' => 'cal-1', 'name' => 'Primary', 'primary' => true],
                    ],
                    'error' => null,
                ]);

            app()->instance(CalendarProviderEnum::Google->getService(), $mockService);

            $result = $this->service->fetchCalendars($this->user, CalendarProviderEnum::Google);

            expect($result['success'])->toBeTrue()
                ->and($result['calendars'])->toHaveCount(1)
                ->and($result['calendars'][0]['name'])->toBe('Primary');
        });

        it('throws when no integration exists', function (): void {
            $this->service->fetchCalendars($this->user, CalendarProviderEnum::Google);
        })->throws(ModelNotFoundException::class);
    });

    describe('selectCalendar', function (): void {
        it('delegates calendar selection to the resolved service', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::Google,
            ]);

            $mockService = Mockery::mock(ExternalCalendarServiceInterface::class);
            $mockService->shouldReceive('selectCalendar')
                ->once()
                ->with(
                    Mockery::on(fn ($u): bool => $u->getKey() === $this->user->getKey()),
                    'cal-123',
                    'My Calendar',
                )
                ->andReturn($integration);

            app()->instance(CalendarProviderEnum::Google->getService(), $mockService);

            $result = $this->service->selectCalendar(
                $this->user,
                CalendarProviderEnum::Google,
                'cal-123',
                'My Calendar',
            );

            expect($result->getKey())->toBe($integration->getKey());
        });
    });

    describe('disconnect', function (): void {
        it('deletes the integration for the given provider', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::Google,
            ]);

            $this->service->disconnect($this->user, CalendarProviderEnum::Google);

            expect(UserCalendarIntegration::query()->find($integration->getKey()))->toBeNull();
        });

        it('does not delete integrations for other providers', function (): void {
            $googleIntegration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::Google,
            ]);

            $outlookIntegration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::Outlook,
            ]);

            $this->service->disconnect($this->user, CalendarProviderEnum::Google);

            expect(UserCalendarIntegration::query()->find($googleIntegration->getKey()))->toBeNull()
                ->and(UserCalendarIntegration::query()->find($outlookIntegration->getKey()))->not->toBeNull();
        });

        it('does not delete integrations for other users', function (): void {
            $otherUser = User::factory()->create();

            $myIntegration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::Google,
            ]);

            $otherIntegration = UserCalendarIntegration::factory()->create([
                'user_id'  => $otherUser->getKey(),
                'provider' => CalendarProviderEnum::Google,
            ]);

            $this->service->disconnect($this->user, CalendarProviderEnum::Google);

            expect(UserCalendarIntegration::query()->find($myIntegration->getKey()))->toBeNull()
                ->and(UserCalendarIntegration::query()->find($otherIntegration->getKey()))->not->toBeNull();
        });
    });
});
