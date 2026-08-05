<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Modules\ExternalCalendar\Actions\ExternalCalendar\ExternalCalendarConnectDirect;
use Modules\ExternalCalendar\Enums\CalendarProviderEnum;
use Modules\ExternalCalendar\Models\UserCalendarIntegration;
use Modules\ExternalCalendar\Services\ExternalCalendarSynchronizationService;

mutates(ExternalCalendarConnectDirect::class);

describe('ExternalCalendarConnectDirect', function (): void {
    beforeEach(function (): void {
        config(['calendar.encryption_key1' => base64_encode(random_bytes(32))]);
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
    });

    it('redirects to profile.edit with error for unknown provider', function (): void {
        $response = $this->actingAs($this->user)
            ->post(route('external-calendar.connect.direct', ['provider' => 'unknown']), [
                'client_id'     => 'apple@example.com',
                'client_secret' => 'app-specific-password-123',
            ]);

        $response->assertRedirect(route('profile.edit'));

        expect(session('error'))->not->toBeEmpty();
    });

    it('redirects to profile.edit with error for non-CalDAV provider', function (): void {
        $response = $this->actingAs($this->user)
            ->post(route('external-calendar.connect.direct', ['provider' => 'google']), [
                'client_id'     => 'apple@example.com',
                'client_secret' => 'app-specific-password-123',
            ]);

        $response->assertRedirect(route('profile.edit'));

        expect(session('error'))->not->toBeEmpty();
    });

    it('redirects to profile.edit with error when client_id is missing', function (): void {
        $response = $this->actingAs($this->user)
            ->post(route('external-calendar.connect.direct', ['provider' => 'apple']), [
                'client_secret' => 'app-specific-password-123',
            ]);

        $response->assertRedirect(route('profile.edit'));

        expect(session('error'))->not->toBeEmpty();
    });

    it('redirects to profile.edit with error when client_id is not an email', function (): void {
        $response = $this->actingAs($this->user)
            ->post(route('external-calendar.connect.direct', ['provider' => 'apple']), [
                'client_id'     => 'not-an-email',
                'client_secret' => 'app-specific-password-123',
            ]);

        $response->assertRedirect(route('profile.edit'));

        expect(session('error'))->not->toBeEmpty();
    });

    it('redirects to profile.edit with error when client_secret is missing', function (): void {
        $response = $this->actingAs($this->user)
            ->post(route('external-calendar.connect.direct', ['provider' => 'apple']), [
                'client_id' => 'apple@example.com',
            ]);

        $response->assertRedirect(route('profile.edit'));

        expect(session('error'))->not->toBeEmpty();
    });

    it('redirects to profile.edit with error when client_secret is too short', function (): void {
        $response = $this->actingAs($this->user)
            ->post(route('external-calendar.connect.direct', ['provider' => 'apple']), [
                'client_id'     => 'apple@example.com',
                'client_secret' => 'short',
            ]);

        $response->assertRedirect(route('profile.edit'));

        expect(session('error'))->not->toBeEmpty();
    });

    it('cleans up integration on validation failure', function (): void {
        UserCalendarIntegration::factory()->create([
            'user_id'  => $this->user->getKey(),
            'provider' => CalendarProviderEnum::APPLE,
        ]);

        $this->actingAs($this->user)
            ->post(route('external-calendar.connect.direct', ['provider' => 'apple']), [
                'client_id'     => 'not-an-email',
                'client_secret' => 'app-specific-password-123',
            ]);

        expect(UserCalendarIntegration::query()
            ->where('user_id', $this->user->getKey())
            ->where('provider', CalendarProviderEnum::APPLE)
            ->exists()
        )->toBeFalse();
    });

    it('saves credentials and redirects with calendars on success', function (): void {
        $syncService = Mockery::mock(ExternalCalendarSynchronizationService::class);
        $syncService->shouldReceive('saveCredentials')
            ->once()
            ->with(
                Mockery::on(fn ($u): bool => $u->getKey() === $this->user->getKey()),
                CalendarProviderEnum::APPLE,
                'apple@example.com',
                'app-specific-password-123',
            );

        $syncService->shouldReceive('fetchCalendars')
            ->once()
            ->with(
                Mockery::on(fn ($u): bool => $u->getKey() === $this->user->getKey()),
                CalendarProviderEnum::APPLE,
            )
            ->andReturn([
                'success'   => true,
                'calendars' => [['id' => 'home', 'name' => 'Home', 'primary' => true]],
                'error'     => null,
            ]);

        app()->instance(ExternalCalendarSynchronizationService::class, $syncService);

        $response = $this->actingAs($this->user)
            ->post(route('external-calendar.connect.direct', ['provider' => 'apple']), [
                'client_id'     => 'apple@example.com',
                'client_secret' => 'app-specific-password-123',
            ]);

        $response->assertRedirect(route('profile.edit'));
        expect(session('calendar_provider'))->toBe(CalendarProviderEnum::APPLE->value)
            ->and(session('calendars'))->toHaveCount(1);
    });

    it('redirects with error and cleans up integration when no calendars are found', function (): void {
        UserCalendarIntegration::factory()->create([
            'user_id'  => $this->user->getKey(),
            'provider' => CalendarProviderEnum::APPLE,
        ]);

        $syncService = Mockery::mock(ExternalCalendarSynchronizationService::class);
        $syncService->shouldReceive('saveCredentials')->once();
        $syncService->shouldReceive('fetchCalendars')
            ->once()
            ->andReturn([
                'success'   => false,
                'calendars' => [],
                'error'     => 'Authentication failed. Check your Apple ID and App-Specific Password.',
            ]);

        app()->instance(ExternalCalendarSynchronizationService::class, $syncService);

        $response = $this->actingAs($this->user)
            ->post(route('external-calendar.connect.direct', ['provider' => 'apple']), [
                'client_id'     => 'apple@example.com',
                'client_secret' => 'app-specific-password-123',
            ]);

        $response->assertRedirect(route('profile.edit'));
        expect(session('error'))->toBe('Authentication failed. Check your Apple ID and App-Specific Password.')
            ->and(UserCalendarIntegration::query()
                ->where('user_id', $this->user->getKey())
                ->where('provider', CalendarProviderEnum::APPLE)
                ->exists()
            )->toBeFalse();
    });

    it('uses default error message when fetchCalendars returns no error string', function (): void {
        $syncService = Mockery::mock(ExternalCalendarSynchronizationService::class);
        $syncService->shouldReceive('saveCredentials')->once();
        $syncService->shouldReceive('fetchCalendars')
            ->once()
            ->andReturn([
                'success'   => false,
                'calendars' => [],
                'error'     => null,
            ]);

        app()->instance(ExternalCalendarSynchronizationService::class, $syncService);

        $response = $this->actingAs($this->user)
            ->post(route('external-calendar.connect.direct', ['provider' => 'apple']), [
                'client_id'     => 'apple@example.com',
                'client_secret' => 'app-specific-password-123',
            ]);

        $response->assertRedirect(route('profile.edit'));

        expect(session('error'))->toBe('No calendars found. Please check your credentials and try again.');
    });

    it('requires authentication', function (): void {
        $response = $this->post(route('external-calendar.connect.direct', ['provider' => 'apple']), [
            'client_id'     => 'apple@example.com',
            'client_secret' => 'app-specific-password-123',
        ]);

        $response->assertRedirect(route('login'));
    });
});
