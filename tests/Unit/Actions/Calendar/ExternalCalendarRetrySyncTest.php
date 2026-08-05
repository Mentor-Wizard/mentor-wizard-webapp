<?php

declare(strict_types=1);

use App\Actions\Calendar\ExternalCalendar\ExternalCalendarRetrySync;
use App\Enums\CalendarProviderEnum;
use App\Enums\CalendarSyncStatusEnum;
use App\Jobs\ProcessCalendarEventExternalCalendarIntegrations;
use App\Models\ExternalCalendarEvent;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Queue;
use Modules\Calendar\Enums\CalendarEventRoleEnum;
use Modules\Calendar\Enums\CalendarEventStatusEnum;
use Modules\Calendar\Models\CalendarEvent;

mutates(ExternalCalendarRetrySync::class);

describe('ExternalCalendarRetrySync', function (): void {
    beforeEach(function (): void {
        config(['calendar.encryption_key1' => base64_encode(random_bytes(32))]);
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
    });

    it('redirects to profile.edit with error for unknown provider', function (): void {
        Queue::fake();

        $response = $this->actingAs($this->user)
            ->post(route('external-calendar.retry', ['provider' => 'unknown']));

        $response->assertRedirect(route('profile.edit'));

        expect(session('error'))->not->toBeEmpty();
    });

    it('requires authentication', function (): void {
        Queue::fake();

        $this->post(route('external-calendar.retry', ['provider' => 'google']))
            ->assertRedirect(route('login'));
    });

    it('redirects to profile.edit with error when no integration exists for the provider', function (): void {
        Queue::fake();

        $response = $this->actingAs($this->user)
            ->post(route('external-calendar.retry', ['provider' => 'google']));

        $response->assertRedirect(route('profile.edit'));

        expect(session('error'))->toBe('No calendar integration found for this provider.');
    });

    it('resets sync_status to active and clears last_error_message', function (): void {
        $integration = UserCalendarIntegration::factory()->create([
            'user_id'            => $this->user->getKey(),
            'provider'           => CalendarProviderEnum::GOOGLE,
            'sync_status'        => CalendarSyncStatusEnum::ERROR,
            'last_error_message' => 'Token expired.',
        ]);

        Queue::fake();

        $this->actingAs($this->user)
            ->post(route('external-calendar.retry', ['provider' => 'google']))
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('success');

        expect($integration->refresh())
            ->sync_status->toBe(CalendarSyncStatusEnum::ACTIVE)
            ->last_error_message->toBeNull();
    });

    it('dispatches sync job for a confirmed event without existing external sync', function (): void {
        UserCalendarIntegration::factory()->create([
            'user_id'     => $this->user->getKey(),
            'provider'    => CalendarProviderEnum::GOOGLE,
            'sync_status' => CalendarSyncStatusEnum::ERROR,
        ]);

        $event = CalendarEvent::factory()->create([
            'status'          => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time' => Date::tomorrow()->setTime(10, 0),
            'end_date_time'   => Date::tomorrow()->setTime(11, 0),
        ]);
        $event->calendarEventUsers()->attach($this->user->getKey(), [
            'role' => CalendarEventRoleEnum::HOST->value,
        ]);

        Queue::fake();

        $this->actingAs($this->user)
            ->post(route('external-calendar.retry', ['provider' => 'google']));

        Queue::assertPushed(
            ProcessCalendarEventExternalCalendarIntegrations::class,
            fn ($job): bool => $job->calendarEvent->getKey() === $event->getKey(),
        );
    });

    it('does not dispatch sync for events already synced for this provider', function (): void {
        UserCalendarIntegration::factory()->create([
            'user_id'     => $this->user->getKey(),
            'provider'    => CalendarProviderEnum::GOOGLE,
            'sync_status' => CalendarSyncStatusEnum::ERROR,
        ]);

        $event = CalendarEvent::factory()->create(['status' => CalendarEventStatusEnum::CONFIRMED]);
        $event->calendarEventUsers()->attach($this->user->getKey());

        ExternalCalendarEvent::query()->create([
            'calendar_event_id' => $event->getKey(),
            'user_id'           => $this->user->getKey(),
            'provider'          => CalendarProviderEnum::GOOGLE,
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
            'provider'    => CalendarProviderEnum::GOOGLE,
            'sync_status' => CalendarSyncStatusEnum::ERROR,
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
            'provider'    => CalendarProviderEnum::GOOGLE,
            'sync_status' => CalendarSyncStatusEnum::ERROR,
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
            'provider'    => CalendarProviderEnum::GOOGLE,
            'sync_status' => CalendarSyncStatusEnum::ERROR,
        ]);

        $events = CalendarEvent::factory(3)->create([
            'status'          => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time' => Date::tomorrow()->setTime(10, 0),
            'end_date_time'   => Date::tomorrow()->setTime(11, 0),
        ]);
        foreach ($events as $event) {
            $event->calendarEventUsers()->attach($this->user->getKey(), [
                'role' => CalendarEventRoleEnum::HOST->value,
            ]);
        }

        Queue::fake();

        $this->actingAs($this->user)
            ->post(route('external-calendar.retry', ['provider' => 'google']));

        Queue::assertPushed(ProcessCalendarEventExternalCalendarIntegrations::class, 3);
    });
});
