<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Modules\ExternalCalendar\Actions\ExternalCalendar\ExternalCalendarSelectCalendar;
use Modules\ExternalCalendar\Enums\CalendarProviderEnum;
use Modules\ExternalCalendar\Models\UserCalendarIntegration;
use Modules\ExternalCalendar\Services\Contracts\ExternalCalendarServiceInterface;
use Modules\ExternalCalendar\Services\ExternalCalendarServiceFactory;

mutates(ExternalCalendarSelectCalendar::class);

describe('ExternalCalendarSelectCalendar', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
    });

    it('selects a calendar and redirects back with success message', function (): void {
        $integration = UserCalendarIntegration::factory()->create([
            'user_id'  => $this->user->getKey(),
            'provider' => CalendarProviderEnum::GOOGLE,
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

        $factory = Mockery::mock(ExternalCalendarServiceFactory::class);
        $factory->shouldReceive('for')
            ->with(CalendarProviderEnum::GOOGLE)
            ->andReturn($service);

        app()->instance(ExternalCalendarServiceFactory::class, $factory);

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

    it('rejects a CalDAV calendar_id pointing at a non-icloud host (SSRF)', function (): void {
        UserCalendarIntegration::factory()->create([
            'user_id'  => $this->user->getKey(),
            'provider' => CalendarProviderEnum::APPLE,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('external-calendar.select', ['provider' => 'apple']), [
                'calendar_id'   => 'http://169.254.169.254/latest/meta-data/iam/security-credentials/',
                'calendar_name' => 'Malicious',
            ]);

        $response->assertRedirect(route('profile.edit'));

        expect(session('error'))->not->toBeEmpty();

        $this->assertDatabaseMissing(UserCalendarIntegration::class, [
            'user_id'      => $this->user->getKey(),
            'provider'     => CalendarProviderEnum::APPLE,
            'calendar_id'  => 'http://169.254.169.254/latest/meta-data/iam/security-credentials/',
        ]);
    });

    it('accepts a CalDAV calendar_id on a sharded icloud.com host', function (): void {
        $integration = UserCalendarIntegration::factory()->create([
            'user_id'  => $this->user->getKey(),
            'provider' => CalendarProviderEnum::APPLE,
        ]);

        $service = Mockery::mock(ExternalCalendarServiceInterface::class);
        $service->shouldReceive('selectCalendar')
            ->once()
            ->with(
                Mockery::on(fn ($u): bool => $u->getKey() === $this->user->getKey()),
                'https://p52-caldav.icloud.com/1234/calendars/home/',
                'Home',
            )
            ->andReturn($integration);

        $factory = Mockery::mock(ExternalCalendarServiceFactory::class);
        $factory->shouldReceive('for')
            ->with(CalendarProviderEnum::APPLE)
            ->andReturn($service);

        app()->instance(ExternalCalendarServiceFactory::class, $factory);

        $response = $this->actingAs($this->user)
            ->post(route('external-calendar.select', ['provider' => 'apple']), [
                'calendar_id'   => 'https://p52-caldav.icloud.com/1234/calendars/home/',
                'calendar_name' => 'Home',
            ]);

        $response->assertRedirect()
            ->assertSessionHas('success', 'Calendar selected successfully.');
    });
});
