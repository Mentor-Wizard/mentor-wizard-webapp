<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\ExternalCalendar\Enums\CalendarProviderEnum;
use Modules\ExternalCalendar\Models\ExternalCalendarEvent;
use Modules\ExternalCalendar\Models\UserCalendarIntegration;
use Modules\ExternalCalendar\Services\Contracts\ExternalCalendarServiceInterface;
use Modules\ExternalCalendar\Services\Contracts\OAuthCalendarServiceInterface;
use Modules\ExternalCalendar\Services\ExternalCalendarServiceFactory;
use Modules\ExternalCalendar\Services\ExternalCalendarSynchronizationService;

mutates(ExternalCalendarSynchronizationService::class);

describe('ExternalCalendarSynchronizationService', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        $this->factory = Mockery::mock(ExternalCalendarServiceFactory::class);
        $this->service = new ExternalCalendarSynchronizationService($this->factory);
    });

    describe('saveCredentialsAndBuildOAuthUrl', function (): void {
        it('saves credentials and returns an OAuth URL', function (): void {
            $mockService = Mockery::mock(OAuthCalendarServiceInterface::class);
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

            $this->factory->shouldReceive('for')
                ->once()
                ->with(CalendarProviderEnum::GOOGLE_PERSONAL_APP)
                ->andReturn($mockService);

            $url = $this->service->saveCredentialsAndBuildOAuthUrl(
                $this->user,
                CalendarProviderEnum::GOOGLE_PERSONAL_APP,
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

            $this->factory->shouldReceive('for')
                ->once()
                ->with(CalendarProviderEnum::APPLE)
                ->andReturn($mockService);

            $this->service->saveCredentials(
                $this->user,
                CalendarProviderEnum::APPLE,
                'apple-id',
                'app-password',
            );
        });
    });

    describe('handleCallback', function (): void {
        it('delegates callback handling to the resolved service', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::GOOGLE,
            ]);

            $mockService = Mockery::mock(OAuthCalendarServiceInterface::class);
            $mockService->shouldReceive('handleCallback')
                ->once()
                ->with(
                    Mockery::on(fn ($u): bool => $u->getKey() === $this->user->getKey()),
                    'auth-code-123',
                )
                ->andReturn($integration);

            $this->factory->shouldReceive('for')
                ->once()
                ->with(CalendarProviderEnum::GOOGLE)
                ->andReturn($mockService);

            $result = $this->service->handleCallback(
                $this->user,
                CalendarProviderEnum::GOOGLE,
                'auth-code-123',
            );

            expect($result->getKey())->toBe($integration->getKey());
        });
    });

    describe('fetchCalendars', function (): void {
        it('fetches calendars for an existing integration', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::GOOGLE,
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

            $this->factory->shouldReceive('for')
                ->once()
                ->with(CalendarProviderEnum::GOOGLE)
                ->andReturn($mockService);

            $result = $this->service->fetchCalendars($this->user, CalendarProviderEnum::GOOGLE);

            expect($result['success'])->toBeTrue()
                ->and($result['calendars'])->toHaveCount(1)
                ->and($result['calendars'][0]['name'])->toBe('Primary');
        });

        it('throws when no integration exists', function (): void {
            $this->service->fetchCalendars($this->user, CalendarProviderEnum::GOOGLE);
        })->throws(ModelNotFoundException::class);
    });

    describe('selectCalendar', function (): void {
        it('delegates calendar selection to the resolved service', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::GOOGLE,
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

            $this->factory->shouldReceive('for')
                ->once()
                ->with(CalendarProviderEnum::GOOGLE)
                ->andReturn($mockService);

            $result = $this->service->selectCalendar(
                $this->user,
                CalendarProviderEnum::GOOGLE,
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
                'provider' => CalendarProviderEnum::GOOGLE,
            ]);

            $this->service->disconnect($this->user, CalendarProviderEnum::GOOGLE);

            expect(UserCalendarIntegration::query()->find($integration->getKey()))->toBeNull();
        });

        it('does not delete integrations for other providers', function (): void {
            $googleIntegration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::GOOGLE,
            ]);

            $outlookIntegration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::OUTLOOK,
            ]);

            $this->service->disconnect($this->user, CalendarProviderEnum::GOOGLE);

            expect(UserCalendarIntegration::query()->find($googleIntegration->getKey()))->toBeNull()
                ->and(UserCalendarIntegration::query()->find($outlookIntegration->getKey()))->not->toBeNull();
        });

        it('does not delete integrations for other users', function (): void {
            $otherUser = User::factory()->create();

            $myIntegration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::GOOGLE,
            ]);

            $otherIntegration = UserCalendarIntegration::factory()->create([
                'user_id'  => $otherUser->getKey(),
                'provider' => CalendarProviderEnum::GOOGLE,
            ]);

            $this->service->disconnect($this->user, CalendarProviderEnum::GOOGLE);

            expect(UserCalendarIntegration::query()->find($myIntegration->getKey()))->toBeNull()
                ->and(UserCalendarIntegration::query()->find($otherIntegration->getKey()))->not->toBeNull();
        });

        it('deletes ExternalCalendarEvent rows for the disconnected provider', function (): void {
            UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::GOOGLE,
            ]);

            $externalEvent = ExternalCalendarEvent::factory()->google()->create([
                'user_id' => $this->user->getKey(),
            ]);

            $this->service->disconnect($this->user, CalendarProviderEnum::GOOGLE);

            expect(ExternalCalendarEvent::query()->find($externalEvent->getKey()))->toBeNull();
        });

        it('does not delete ExternalCalendarEvent rows for other providers on disconnect', function (): void {
            UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::GOOGLE,
            ]);

            $outlookEvent = ExternalCalendarEvent::factory()->outlook()->create([
                'user_id' => $this->user->getKey(),
            ]);

            $this->service->disconnect($this->user, CalendarProviderEnum::GOOGLE);

            expect(ExternalCalendarEvent::query()->find($outlookEvent->getKey()))->not->toBeNull();
        });
    });
});
