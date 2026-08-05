<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Modules\ExternalCalendar\Actions\Pages\ExternalCalendarSettingsPage;
use Modules\ExternalCalendar\Enums\CalendarProviderEnum;
use Modules\ExternalCalendar\Enums\CalendarSyncStatusEnum;
use Modules\ExternalCalendar\Models\UserCalendarIntegration;

mutates(ExternalCalendarSettingsPage::class);

describe('ExternalCalendarSettingsPage', function (): void {
    beforeEach(function (): void {
        config(['calendar.encryption_key1' => base64_encode(random_bytes(32))]);
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
    });

    it('renders the external calendar settings page', function (): void {
        $response = $this->actingAs($this->user)
            ->get(route('pages.settings.external-calendar'));

        $response->assertSuccessful()
            ->assertInertia(
                fn ($page) => $page
                    ->component('ExternalCalendar/SettingsPage')
                    ->has('calendarIntegrations'),
            );
    });

    it('requires authentication', function (): void {
        $this->get(route('pages.settings.external-calendar'))
            ->assertRedirect(route('login'));
    });

    it('returns all providers in the list', function (): void {
        $response = $this->actingAs($this->user)
            ->get(route('pages.settings.external-calendar'));

        $response->assertInertia(
            fn ($page) => $page->has('calendarIntegrations', count(CalendarProviderEnum::cases())),
        );
    });

    it('shows disconnected state for providers without integration', function (): void {
        $response = $this->actingAs($this->user)
            ->get(route('pages.settings.external-calendar'));

        $response->assertInertia(
            fn ($page) => $page->where('calendarIntegrations.0.connected', false)
                ->where('calendarIntegrations.0.sync_status', CalendarSyncStatusEnum::DISCONNECTED->value)
                ->where('calendarIntegrations.0.needs_reauth', false)
                ->where('calendarIntegrations.0.calendar_id', null)
                ->where('calendarIntegrations.0.calendar_name', null),
        );
    });

    it('shows connected state for a provider with an active integration', function (): void {
        // Google is CalendarProviderEnum::cases()[0]
        UserCalendarIntegration::factory()->create([
            'user_id'       => $this->user->getKey(),
            'provider'      => CalendarProviderEnum::GOOGLE,
            'sync_status'   => CalendarSyncStatusEnum::ACTIVE,
            'calendar_id'   => 'primary',
            'calendar_name' => 'My Calendar',
            'needs_reauth'  => false,
        ]);

        $this->actingAs($this->user)
            ->get(route('pages.settings.external-calendar'))
            ->assertInertia(
                fn ($page) => $page
                    ->where('calendarIntegrations.0.key', CalendarProviderEnum::GOOGLE->value)
                    ->where('calendarIntegrations.0.connected', true)
                    ->where('calendarIntegrations.0.sync_status', CalendarSyncStatusEnum::ACTIVE->value)
                    ->where('calendarIntegrations.0.calendar_id', 'primary')
                    ->where('calendarIntegrations.0.calendar_name', 'My Calendar')
                    ->where('calendarIntegrations.0.needs_reauth', false),
            );
    });

    it('shows needs_reauth flag when integration requires reauthorization', function (): void {
        UserCalendarIntegration::factory()->needsReauth()->create([
            'user_id'  => $this->user->getKey(),
            'provider' => CalendarProviderEnum::GOOGLE,
        ]);

        $this->actingAs($this->user)
            ->get(route('pages.settings.external-calendar'))
            ->assertInertia(
                fn ($page) => $page
                    ->where('calendarIntegrations.0.key', CalendarProviderEnum::GOOGLE->value)
                    ->where('calendarIntegrations.0.needs_reauth', true)
                    ->where('calendarIntegrations.0.sync_status', CalendarSyncStatusEnum::ERROR->value),
            );
    });

    it('includes last_error_message when integration has an error', function (): void {
        UserCalendarIntegration::factory()->create([
            'user_id'            => $this->user->getKey(),
            'provider'           => CalendarProviderEnum::GOOGLE,
            'sync_status'        => CalendarSyncStatusEnum::ERROR,
            'last_error_message' => 'Token expired.',
        ]);

        $this->actingAs($this->user)
            ->get(route('pages.settings.external-calendar'))
            ->assertInertia(
                fn ($page) => $page
                    ->where('calendarIntegrations.0.key', CalendarProviderEnum::GOOGLE->value)
                    ->where('calendarIntegrations.0.last_error_message', 'Token expired.'),
            );
    });

    it('does not expose integrations from other users', function (): void {
        $otherUser = User::factory()->create();

        UserCalendarIntegration::factory()->create([
            'user_id'  => $otherUser->getKey(),
            'provider' => CalendarProviderEnum::GOOGLE,
        ]);

        $this->actingAs($this->user)
            ->get(route('pages.settings.external-calendar'))
            ->assertInertia(
                fn ($page) => $page
                    ->where('calendarIntegrations.0.key', CalendarProviderEnum::GOOGLE->value)
                    ->where('calendarIntegrations.0.connected', false),
            );
    });
});
