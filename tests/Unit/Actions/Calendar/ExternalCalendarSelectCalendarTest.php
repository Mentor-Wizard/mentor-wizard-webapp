<?php

declare(strict_types=1);

use App\Actions\Calendar\ExternalCalendarSelectCalendar;
use App\Enums\CalendarProviderEnum;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use App\Services\ExternalCalendar\ExternalCalendarServiceInterface;
use Database\Seeders\RoleSeeder;

mutates(ExternalCalendarSelectCalendar::class);

describe('ExternalCalendarSelectCalendar', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
    });

    it('selects a calendar and redirects back with success message', function (): void {
        $integration = UserCalendarIntegration::factory()->create([
            'user_id'  => $this->user->getKey(),
            'provider' => CalendarProviderEnum::Google,
        ]);

        $service = Mockery::mock(ExternalCalendarServiceInterface::class);
        $service->shouldReceive('selectCalendar')
            ->once()
            ->with(
                Mockery::on(fn ($u): bool => $u->getKey() === $this->user->getKey()),
                'primary-calendar-id',
                'My Primary Calendar',
            )
            ->andReturn($integration);

        app()->instance(CalendarProviderEnum::Google->getService(), $service);

        $response = $this->actingAs($this->user)
            ->post(route('external-calendar.select', ['provider' => 'google']), [
                'calendar_id'   => 'primary-calendar-id',
                'calendar_name' => 'My Primary Calendar',
            ]);

        $response->assertRedirect()
            ->assertSessionHas('success', 'Calendar selected successfully.');
    });

    it('redirects to profile.edit with error for invalid provider', function (): void {
        $response = $this->actingAs($this->user)
            ->post(route('external-calendar.select', ['provider' => 'not-a-provider']), [
                'calendar_id'   => 'some-id',
                'calendar_name' => 'Some Calendar',
            ]);

        $response->assertRedirect(route('profile.edit'));
        expect(session('error'))->not->toBeEmpty();
    });

    it('redirects to profile.edit with error when calendar_id is missing', function (): void {
        $response = $this->actingAs($this->user)
            ->post(route('external-calendar.select', ['provider' => 'google']), [
                'calendar_name' => 'My Calendar',
            ]);

        $response->assertRedirect(route('profile.edit'));
        expect(session('error'))->not->toBeEmpty();
    });

    it('redirects to profile.edit with error when calendar_name is missing', function (): void {
        $response = $this->actingAs($this->user)
            ->post(route('external-calendar.select', ['provider' => 'google']), [
                'calendar_id' => 'some-id',
            ]);

        $response->assertRedirect(route('profile.edit'));
        expect(session('error'))->not->toBeEmpty();
    });

    it('requires authentication', function (): void {
        $response = $this->post(route('external-calendar.select', ['provider' => 'google']), [
            'calendar_id'   => 'some-id',
            'calendar_name' => 'My Calendar',
        ]);

        $response->assertRedirect(route('login'));
    });
});
