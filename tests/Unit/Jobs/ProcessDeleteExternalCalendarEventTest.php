<?php

declare(strict_types=1);

use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Enums\CalendarProviderEnum;
use App\Enums\CalendarSyncStatusEnum;
use App\Jobs\DeleteExternalCalendarEvent;
use App\Jobs\ProcessDeleteExternalCalendarEvent;
use App\Models\CalendarEvent;
use App\Models\ExternalCalendarEvent;
use App\Models\MentorProgram;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Date;

mutates(ProcessDeleteExternalCalendarEvent::class);

describe('ProcessDeleteExternalCalendarEvent job', function (): void {
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
            'provider'    => CalendarProviderEnum::Google,
            'sync_status' => CalendarSyncStatusEnum::Active,
        ]);

        $this->externalEvent = ExternalCalendarEvent::query()->create([
            'calendar_event_id' => $this->event->getKey(),
            'user_id'           => $this->user->getKey(),
            'provider'          => CalendarProviderEnum::Google,
            'external_event_id' => 'ext-event-456',
        ]);
    });

    it('dispatches DeleteExternalCalendarEvent with the fetched integration', function (): void {
        Bus::fake();

        new ProcessDeleteExternalCalendarEvent($this->event->getKey())->handle();

        Bus::assertDispatched(fn (DeleteExternalCalendarEvent $job): bool => $job->externalEvent->getKey() === $this->externalEvent->getKey()
            && $job->integration?->getKey() === $this->integration->getKey());
    });

    it('passes null integration when no matching integration exists', function (): void {
        Bus::fake();

        $this->integration->delete();

        new ProcessDeleteExternalCalendarEvent($this->event->getKey())->handle();

        Bus::assertDispatched(fn (DeleteExternalCalendarEvent $job): bool => $job->externalEvent->getKey() === $this->externalEvent->getKey()
            && ! $job->integration instanceof UserCalendarIntegration);
    });

    it('dispatches one job per external event with correct integrations', function (): void {
        Bus::fake();

        $secondUser = User::factory()->create();
        $secondIntegration = UserCalendarIntegration::factory()->create([
            'user_id'     => $secondUser->getKey(),
            'provider'    => CalendarProviderEnum::Outlook,
            'sync_status' => CalendarSyncStatusEnum::Active,
        ]);

        $secondExternalEvent = ExternalCalendarEvent::query()->create([
            'calendar_event_id' => $this->event->getKey(),
            'user_id'           => $secondUser->getKey(),
            'provider'          => CalendarProviderEnum::Outlook,
            'external_event_id' => 'ext-event-789',
        ]);

        new ProcessDeleteExternalCalendarEvent($this->event->getKey())->handle();

        Bus::assertDispatchedTimes(DeleteExternalCalendarEvent::class, 2);
        Bus::assertDispatched(fn (DeleteExternalCalendarEvent $job): bool => $job->externalEvent->getKey() === $this->externalEvent->getKey()
            && $job->integration?->getKey() === $this->integration->getKey());
        Bus::assertDispatched(fn (DeleteExternalCalendarEvent $job): bool => $job->externalEvent->getKey() === $secondExternalEvent->getKey()
            && $job->integration?->getKey() === $secondIntegration->getKey());
    });

    it('does nothing when no external events exist', function (): void {
        Bus::fake();

        $this->externalEvent->delete();

        new ProcessDeleteExternalCalendarEvent($this->event->getKey())->handle();

        Bus::assertNotDispatched(DeleteExternalCalendarEvent::class);
    });
});
