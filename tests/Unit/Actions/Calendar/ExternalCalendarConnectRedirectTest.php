<?php

declare(strict_types=1);

use App\Actions\Calendar\ExternalCalendarConnectRedirect;
use App\Enums\CalendarProviderEnum;
use App\Models\User;
use App\Services\ExternalCalendar\ExternalCalendarSynchronizationService;
use Database\Seeders\RoleSeeder;

mutates(ExternalCalendarConnectRedirect::class);

describe('ExternalCalendarConnectRedirect', function (): void {
    beforeEach(function (): void {
        config(['calendar.encryption_key1' => base64_encode(random_bytes(32))]);
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
    });

    it('aborts with 422 for unknown provider', function (): void {
        $response = $this->actingAs($this->user)
            ->post(route('external-calendar.connect.redirect', ['provider' => 'unknown']));

        $response->assertStatus(422);
    });

    it('redirects to OAuth URL for app-credentials provider without requiring client credentials', function (): void {
        $syncService = Mockery::mock(ExternalCalendarSynchronizationService::class);
        $syncService->shouldReceive('saveCredentialsAndBuildOAuthUrl')
            ->once()
            ->with(
                Mockery::on(fn ($u): bool => $u->getKey() === $this->user->getKey()),
                CalendarProviderEnum::Google,
                null,
                null,
            )
            ->andReturn('https://accounts.google.com/o/oauth2/auth?scope=calendar');

        app()->instance(ExternalCalendarSynchronizationService::class, $syncService);

        $response = $this->actingAs($this->user)
            ->post(route('external-calendar.connect.redirect', ['provider' => 'google']));

        $response->assertRedirect('https://accounts.google.com/o/oauth2/auth?scope=calendar');
    });

    it('stores oauth pending state in session', function (): void {
        $syncService = Mockery::mock(ExternalCalendarSynchronizationService::class);
        $syncService->shouldReceive('saveCredentialsAndBuildOAuthUrl')
            ->once()
            ->andReturn('https://accounts.google.com/oauth');

        app()->instance(ExternalCalendarSynchronizationService::class, $syncService);

        $this->actingAs($this->user)
            ->post(route('external-calendar.connect.redirect', ['provider' => 'google']));

        expect(session('calendar_oauth_pending'))->toMatchArray([
            'user_id'  => $this->user->getKey(),
            'provider' => CalendarProviderEnum::Google->value,
        ]);
    });

    it('validates client_id and client_secret are required for personal-credentials provider', function (): void {
        $response = $this->actingAs($this->user)
            ->post(route('external-calendar.connect.redirect', ['provider' => 'google_personal_app']));

        $response->assertSessionHasErrors(['client_id', 'client_secret']);
    });

    it('validates minimum length for client credentials', function (): void {
        $response = $this->actingAs($this->user)
            ->post(route('external-calendar.connect.redirect', ['provider' => 'google_personal_app']), [
                'client_id'     => 'short',
                'client_secret' => 'short',
            ]);

        $response->assertSessionHasErrors(['client_id', 'client_secret']);
    });

    it('redirects to OAuth URL for personal-credentials provider with client credentials', function (): void {
        $syncService = Mockery::mock(ExternalCalendarSynchronizationService::class);
        $syncService->shouldReceive('saveCredentialsAndBuildOAuthUrl')
            ->once()
            ->with(
                Mockery::on(fn ($u): bool => $u->getKey() === $this->user->getKey()),
                CalendarProviderEnum::GooglePersonalApp,
                'my-client-id-value',
                'my-client-secret-value',
            )
            ->andReturn('https://accounts.google.com/o/oauth2/auth?client_id=my-client-id-value');

        app()->instance(ExternalCalendarSynchronizationService::class, $syncService);

        $response = $this->actingAs($this->user)
            ->post(route('external-calendar.connect.redirect', ['provider' => 'google_personal_app']), [
                'client_id'     => 'my-client-id-value',
                'client_secret' => 'my-client-secret-value',
            ]);

        $response->assertRedirect('https://accounts.google.com/o/oauth2/auth?client_id=my-client-id-value');
    });

    it('redirects to OAuth URL for Outlook without requiring client credentials', function (): void {
        $syncService = Mockery::mock(ExternalCalendarSynchronizationService::class);
        $syncService->shouldReceive('saveCredentialsAndBuildOAuthUrl')
            ->once()
            ->with(
                Mockery::on(fn ($u): bool => $u->getKey() === $this->user->getKey()),
                CalendarProviderEnum::Outlook,
                null,
                null,
            )
            ->andReturn('https://login.microsoftonline.com/oauth2/v2.0/authorize');

        app()->instance(ExternalCalendarSynchronizationService::class, $syncService);

        $response = $this->actingAs($this->user)
            ->post(route('external-calendar.connect.redirect', ['provider' => 'outlook']));

        $response->assertRedirect('https://login.microsoftonline.com/oauth2/v2.0/authorize');
    });

    it('requires authentication', function (): void {
        $response = $this->post(route('external-calendar.connect.redirect', ['provider' => 'google']));

        $response->assertRedirect(route('login'));
    });
});
