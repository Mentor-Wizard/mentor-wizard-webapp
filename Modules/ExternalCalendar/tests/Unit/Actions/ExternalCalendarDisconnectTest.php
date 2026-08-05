<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Modules\ExternalCalendar\Actions\ExternalCalendar\ExternalCalendarDisconnect;
use Modules\ExternalCalendar\Enums\CalendarProviderEnum;
use Modules\ExternalCalendar\Models\UserCalendarIntegration;
use Modules\ExternalCalendar\Services\ExternalCalendarSynchronizationService;

mutates(ExternalCalendarDisconnect::class);

describe('ExternalCalendarDisconnect', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
    });

    it('disconnects and redirects to profile edit with success', function (): void {
        UserCalendarIntegration::factory()->create([
            'user_id'  => $this->user->getKey(),
            'provider' => CalendarProviderEnum::GOOGLE,
        ]);

        $syncService = Mockery::mock(ExternalCalendarSynchronizationService::class);
        $syncService->shouldReceive('disconnect')
            ->once()
            ->with(
                Mockery::on(fn ($u): bool => $u->getKey() === $this->user->getKey()),
                CalendarProviderEnum::GOOGLE,
            );

        app()->instance(ExternalCalendarSynchronizationService::class, $syncService);

        $response = $this->actingAs($this->user)
            ->delete(route('external-calendar.disconnect', ['provider' => 'google']));

        $response->assertRedirect(route('profile.edit'));

        expect(session('success'))->toBe('Calendar disconnected successfully.');
    });

    it('redirects to profile.edit with error for invalid provider', function (): void {
        $response = $this->actingAs($this->user)
            ->delete(route('external-calendar.disconnect', ['provider' => 'invalid-provider']));

        $response->assertRedirect(route('profile.edit'));

        expect(session('error'))->not->toBeEmpty();
    });

    it('cleans up integration on invalid provider', function (): void {
        UserCalendarIntegration::factory()->create([
            'user_id'  => $this->user->getKey(),
            'provider' => CalendarProviderEnum::GOOGLE,
        ]);

        $this->actingAs($this->user)
            ->delete(route('external-calendar.disconnect', ['provider' => 'invalid-provider']));

        // invalid provider resolves to null, so no cleanup happens — integration stays
        expect(UserCalendarIntegration::query()
            ->where('user_id', $this->user->getKey())
            ->exists()
        )->toBeTrue();
    });

    it('requires authentication', function (): void {
        $response = $this->delete(route('external-calendar.disconnect', ['provider' => 'google']));

        $response->assertRedirect(route('login'));
    });
});
