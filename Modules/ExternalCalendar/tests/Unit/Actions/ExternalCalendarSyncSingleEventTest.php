<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Queue;
use Modules\Calendar\Enums\CalendarEventRoleEnum;
use Modules\Calendar\Models\CalendarEvent;
use Modules\ExternalCalendar\Actions\ExternalCalendar\ExternalCalendarSyncSingleEvent;
use Modules\ExternalCalendar\Enums\CalendarProviderEnum;
use Modules\ExternalCalendar\Enums\CalendarSyncStatusEnum;
use Modules\ExternalCalendar\Jobs\CreateExternalCalendarEvent;
use Modules\ExternalCalendar\Models\ExternalCalendarEvent;
use Modules\ExternalCalendar\Models\UserCalendarIntegration;

mutates(ExternalCalendarSyncSingleEvent::class);

describe('ExternalCalendarSyncSingleEvent', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        $this->calendarEvent = CalendarEvent::factory()->create();
        $this->calendarEvent->calendarEventUsers()->attach($this->user->getKey(), [
            'role' => CalendarEventRoleEnum::PARTICIPANT->value,
        ]);
    });

    it('dispatches sync job and redirects back with success', function (): void {
        UserCalendarIntegration::factory()->create([
            'user_id'     => $this->user->getKey(),
            'provider'    => CalendarProviderEnum::GOOGLE,
            'sync_status' => CalendarSyncStatusEnum::ACTIVE,
        ]);

        Queue::fake();

        $response = $this->actingAs($this->user)
            ->post(route('external-calendar.sync-event', [
                'calendarEvent' => $this->calendarEvent->getKey(),
                'provider'      => 'google',
            ]));

        $response->assertRedirect();

        expect(session('success'))->toBe('Event sync has been queued.');
        Queue::assertPushed(CreateExternalCalendarEvent::class);
    });

    it('redirects back with error for unknown provider', function (): void {
        Queue::fake();

        $response = $this->actingAs($this->user)
            ->post(route('external-calendar.sync-event', [
                'calendarEvent' => $this->calendarEvent->getKey(),
                'provider'      => 'unknown',
            ]));

        $response->assertRedirect();

        expect(session('error'))->not->toBeEmpty();
        Queue::assertNothingPushed();
    });

    it('redirects back with error when user is not a participant in the event', function (): void {
        $otherEvent = CalendarEvent::factory()->create();
        $otherUser = User::factory()->create();
        $otherEvent->calendarEventUsers()->attach($otherUser->getKey(), [
            'role' => CalendarEventRoleEnum::HOST->value,
        ]);

        UserCalendarIntegration::factory()->create([
            'user_id'     => $this->user->getKey(),
            'provider'    => CalendarProviderEnum::GOOGLE,
            'sync_status' => CalendarSyncStatusEnum::ACTIVE,
        ]);

        Queue::fake();

        $response = $this->actingAs($this->user)
            ->post(route('external-calendar.sync-event', [
                'calendarEvent' => $otherEvent->getKey(),
                'provider'      => 'google',
            ]));

        $response->assertRedirect();

        expect(session('error'))->not->toBeEmpty();
        Queue::assertNothingPushed();
    });

    it('redirects back with error when no active integration exists', function (): void {
        Queue::fake();

        $response = $this->actingAs($this->user)
            ->post(route('external-calendar.sync-event', [
                'calendarEvent' => $this->calendarEvent->getKey(),
                'provider'      => 'google',
            ]));

        $response->assertRedirect();

        expect(session('error'))->not->toBeEmpty();
        Queue::assertNothingPushed();
    });

    it('redirects back with error when integration is not active', function (): void {
        UserCalendarIntegration::factory()->create([
            'user_id'     => $this->user->getKey(),
            'provider'    => CalendarProviderEnum::GOOGLE,
            'sync_status' => CalendarSyncStatusEnum::ERROR,
        ]);

        Queue::fake();

        $response = $this->actingAs($this->user)
            ->post(route('external-calendar.sync-event', [
                'calendarEvent' => $this->calendarEvent->getKey(),
                'provider'      => 'google',
            ]));

        $response->assertRedirect();

        expect(session('error'))->not->toBeEmpty();
        Queue::assertNothingPushed();
    });

    it('redirects back with error when event is already synced', function (): void {
        UserCalendarIntegration::factory()->create([
            'user_id'     => $this->user->getKey(),
            'provider'    => CalendarProviderEnum::GOOGLE,
            'sync_status' => CalendarSyncStatusEnum::ACTIVE,
        ]);

        ExternalCalendarEvent::query()->create([
            'calendar_event_id' => $this->calendarEvent->getKey(),
            'user_id'           => $this->user->getKey(),
            'provider'          => CalendarProviderEnum::GOOGLE,
            'external_event_id' => 'ext-456',
        ]);

        Queue::fake();

        $response = $this->actingAs($this->user)
            ->post(route('external-calendar.sync-event', [
                'calendarEvent' => $this->calendarEvent->getKey(),
                'provider'      => 'google',
            ]));

        $response->assertRedirect();

        expect(session('error'))->not->toBeEmpty();
        Queue::assertNothingPushed();
    });

    it('requires authentication', function (): void {
        $response = $this->post(route('external-calendar.sync-event', [
            'calendarEvent' => $this->calendarEvent->getKey(),
            'provider'      => 'google',
        ]));

        $response->assertRedirect(route('login'));
    });
});
