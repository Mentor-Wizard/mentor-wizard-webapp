<?php

declare(strict_types=1);

use App\Actions\Calendar\ExternalCalendarRetrySync;
use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarProviderEnum;
use App\Enums\CalendarSyncStatusEnum;
use App\Jobs\SyncCalendarEventToExternalCalendar;
use App\Models\CalendarEvent;
use App\Models\ExternalCalendarEvent;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Queue;

mutates(ExternalCalendarRetrySync::class);

describe('ExternalCalendarRetrySync', function (): void {
    beforeEach(function (): void {
        config(['calendar.encryption_key1' => base64_encode(random_bytes(32))]);
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
    });

    it('aborts with 422 for unknown provider', function (): void {
        Queue::fake();

        $this->actingAs($this->user)
            ->post(route('external-calendar.retry', ['provider' => 'unknown']))
            ->assertStatus(422);
    });

    it('requires authentication', function (): void {
        Queue::fake();

        $this->post(route('external-calendar.retry', ['provider' => 'google']))
            ->assertRedirect(route('login'));
    });

    it('returns 404 when no integration exists for the provider', function (): void {
        Queue::fake();

        $this->actingAs($this->user)
            ->post(route('external-calendar.retry', ['provider' => 'google']))
            ->assertNotFound();
    });

    it('resets sync_status to active and clears last_error_message', function (): void {
        $integration = UserCalendarIntegration::factory()->create([
            'user_id'            => $this->user->getKey(),
            'provider'           => CalendarProviderEnum::Google,
            'sync_status'        => CalendarSyncStatusEnum::Error,
            'last_error_message' => 'Token expired.',
        ]);

        Queue::fake();

        $this->actingAs($this->user)
            ->post(route('external-calendar.retry', ['provider' => 'google']))
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('success');

        expect($integration->refresh())
            ->sync_status->toBe(CalendarSyncStatusEnum::Active)
            ->last_error_message->toBeNull();
    });

    it('dispatches sync job for a confirmed event without existing external sync', function (): void {
        UserCalendarIntegration::factory()->create([
            'user_id'     => $this->user->getKey(),
            'provider'    => CalendarProviderEnum::Google,
            'sync_status' => CalendarSyncStatusEnum::Error,
        ]);

        $event = CalendarEvent::factory()->create(['status' => CalendarEventStatusEnum::CONFIRMED]);
        $event->calendarEventUsers()->attach($this->user->getKey());

        Queue::fake();

        $this->actingAs($this->user)
            ->post(route('external-calendar.retry', ['provider' => 'google']));

        Queue::assertPushed(
            SyncCalendarEventToExternalCalendar::class,
            fn ($job): bool => $job->calendarEvent->getKey() === $event->getKey(),
        );
    });

    it('does not dispatch sync for events already synced for this provider', function (): void {
        UserCalendarIntegration::factory()->create([
            'user_id'     => $this->user->getKey(),
            'provider'    => CalendarProviderEnum::Google,
            'sync_status' => CalendarSyncStatusEnum::Error,
        ]);

        $event = CalendarEvent::factory()->create(['status' => CalendarEventStatusEnum::CONFIRMED]);
        $event->calendarEventUsers()->attach($this->user->getKey());

        ExternalCalendarEvent::query()->create([
            'calendar_event_id' => $event->getKey(),
            'user_id'           => $this->user->getKey(),
            'provider'          => CalendarProviderEnum::Google,
            'external_event_id' => 'ext-123',
        ]);

        Queue::fake();

        $this->actingAs($this->user)
            ->post(route('external-calendar.retry', ['provider' => 'google']));

        Queue::assertNothingPushed();
    });

    it('does not dispatch sync for non-confirmed events', function (): void {
        UserCalendarIntegration::factory()->create([
            'user_id'     => $this->user->getKey(),
            'provider'    => CalendarProviderEnum::Google,
            'sync_status' => CalendarSyncStatusEnum::Error,
        ]);

        $event = CalendarEvent::factory()->create(['status' => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION]);
        $event->calendarEventUsers()->attach($this->user->getKey());

        Queue::fake();

        $this->actingAs($this->user)
            ->post(route('external-calendar.retry', ['provider' => 'google']));

        Queue::assertNothingPushed();
    });

    it('does not dispatch sync for events the user is not a participant in', function (): void {
        UserCalendarIntegration::factory()->create([
            'user_id'     => $this->user->getKey(),
            'provider'    => CalendarProviderEnum::Google,
            'sync_status' => CalendarSyncStatusEnum::Error,
        ]);

        $otherUser = User::factory()->create();
        $event = CalendarEvent::factory()->create(['status' => CalendarEventStatusEnum::CONFIRMED]);
        $event->calendarEventUsers()->attach($otherUser->getKey());

        Queue::fake();

        $this->actingAs($this->user)
            ->post(route('external-calendar.retry', ['provider' => 'google']));

        Queue::assertNothingPushed();
    });

    it('dispatches multiple jobs when multiple unsynced events exist', function (): void {
        UserCalendarIntegration::factory()->create([
            'user_id'     => $this->user->getKey(),
            'provider'    => CalendarProviderEnum::Google,
            'sync_status' => CalendarSyncStatusEnum::Error,
        ]);

        $events = CalendarEvent::factory(3)->create(['status' => CalendarEventStatusEnum::CONFIRMED]);
        foreach ($events as $event) {
            $event->calendarEventUsers()->attach($this->user->getKey());
        }

        Queue::fake();

        $this->actingAs($this->user)
            ->post(route('external-calendar.retry', ['provider' => 'google']));

        Queue::assertPushed(SyncCalendarEventToExternalCalendar::class, 3);
    });
});
