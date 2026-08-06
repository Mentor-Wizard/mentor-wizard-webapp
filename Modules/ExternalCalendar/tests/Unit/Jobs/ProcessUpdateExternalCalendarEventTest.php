<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Date;
use Modules\Calendar\Enums\CalendarEventStatusEnum;
use Modules\Calendar\Enums\CalendarEventTypeEnum;
use Modules\Calendar\Models\CalendarEvent;
use Modules\ExternalCalendar\Enums\CalendarProviderEnum;
use Modules\ExternalCalendar\Enums\CalendarSyncStatusEnum;
use Modules\ExternalCalendar\Jobs\ProcessUpdateExternalCalendarEvent;
use Modules\ExternalCalendar\Jobs\UpdateExternalCalendarEvent;
use Modules\ExternalCalendar\Models\ExternalCalendarEvent;
use Modules\ExternalCalendar\Models\UserCalendarIntegration;
use Modules\MentorProgram\Models\MentorProgram;

mutates(ProcessUpdateExternalCalendarEvent::class);

describe('ProcessUpdateExternalCalendarEvent job', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        config(['calendar.encryption_key1' => base64_encode(random_bytes(32))]);

        $this->user = User::factory()->create();
        $this->mentorProgram = MentorProgram::factory()->create(['mentor_id' => $this->user->getKey()]);

        $this->event = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => Date::tomorrow()->setTime(10, 0),
            'end_date_time'     => Date::tomorrow()->setTime(11, 0),
            'date'              => Date::tomorrow()->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $this->integration = UserCalendarIntegration::factory()->create([
            'user_id'     => $this->user->getKey(),
            'provider'    => CalendarProviderEnum::GOOGLE,
            'sync_status' => CalendarSyncStatusEnum::ACTIVE,
        ]);

        $this->externalEvent = ExternalCalendarEvent::query()->create([
            'calendar_event_id' => $this->event->getKey(),
            'user_id'           => $this->user->getKey(),
            'provider'          => CalendarProviderEnum::GOOGLE,
            'external_event_id' => 'ext-event-123',
        ]);
    });

    it('dispatches UpdateExternalCalendarEvent with the fetched integration', function (): void {
        Bus::fake();

        new ProcessUpdateExternalCalendarEvent($this->event)->handle();

        Bus::assertDispatched(fn (UpdateExternalCalendarEvent $job): bool => $job->calendarEvent->getKey() === $this->event->getKey()
            && $job->externalEvent->getKey() === $this->externalEvent->getKey()
            && $job->integration->getKey() === $this->integration->getKey());
    });

    it('skips external events whose integration is not active', function (): void {
        Bus::fake();

        $this->integration->update(['sync_status' => CalendarSyncStatusEnum::ERROR]);

        new ProcessUpdateExternalCalendarEvent($this->event)->handle();

        Bus::assertNotDispatched(UpdateExternalCalendarEvent::class);
    });

    it('dispatches one job per active integration across users', function (): void {
        Bus::fake();

        $secondUser = User::factory()->create();
        $secondIntegration = UserCalendarIntegration::factory()->create([
            'user_id'     => $secondUser->getKey(),
            'provider'    => CalendarProviderEnum::OUTLOOK,
            'sync_status' => CalendarSyncStatusEnum::ACTIVE,
        ]);

        $secondExternalEvent = ExternalCalendarEvent::query()->create([
            'calendar_event_id' => $this->event->getKey(),
            'user_id'           => $secondUser->getKey(),
            'provider'          => CalendarProviderEnum::OUTLOOK,
            'external_event_id' => 'ext-event-789',
        ]);

        new ProcessUpdateExternalCalendarEvent($this->event)->handle();

        Bus::assertDispatchedTimes(UpdateExternalCalendarEvent::class, 2);
        Bus::assertDispatched(fn (UpdateExternalCalendarEvent $job): bool => $job->externalEvent->getKey() === $this->externalEvent->getKey()
            && $job->integration->getKey() === $this->integration->getKey());
        Bus::assertDispatched(fn (UpdateExternalCalendarEvent $job): bool => $job->externalEvent->getKey() === $secondExternalEvent->getKey()
            && $job->integration->getKey() === $secondIntegration->getKey());
    });

    it('does nothing when no external events exist', function (): void {
        Bus::fake();

        $this->externalEvent->delete();

        new ProcessUpdateExternalCalendarEvent($this->event)->handle();

        Bus::assertNotDispatched(UpdateExternalCalendarEvent::class);
    });
});
