<?php

declare(strict_types=1);

use App\Actions\Calendar\ExternalCalendarDisconnect;
use App\Enums\CalendarProviderEnum;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use App\Services\ExternalCalendar\ExternalCalendarSynchronizationService;
use Database\Seeders\RoleSeeder;

mutates(ExternalCalendarDisconnect::class);

describe('ExternalCalendarDisconnect', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
    });

    describe('handle', function (): void {
        it('disconnects the integration for the given provider', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::Google,
            ]);

            $syncService = Mockery::mock(ExternalCalendarSynchronizationService::class);
            $syncService->shouldReceive('disconnect')
                ->once()
                ->with(
                    Mockery::on(fn ($u): bool => $u->getKey() === $this->user->getKey()),
                    CalendarProviderEnum::Google,
                );

            $action = new ExternalCalendarDisconnect($syncService);
            $action->handle($this->user, CalendarProviderEnum::Google);
        });
    });

    describe('asController', function (): void {
        it('disconnects and redirects to profile edit', function (): void {
            UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::Google,
            ]);

            $response = $this->actingAs($this->user)
                ->delete(route('external-calendar.disconnect', ['provider' => 'google']));

            $response->assertRedirect(route('profile.edit'));

            expect(UserCalendarIntegration::query()
                ->where('user_id', $this->user->getKey())
                ->where('provider', CalendarProviderEnum::Google)
                ->exists())->toBeFalse();
        });

        it('aborts with 422 for invalid provider', function (): void {
            $response = $this->actingAs($this->user)
                ->delete(route('external-calendar.disconnect', ['provider' => 'invalid-provider']));

            $response->assertStatus(422);
        });
    });
});
