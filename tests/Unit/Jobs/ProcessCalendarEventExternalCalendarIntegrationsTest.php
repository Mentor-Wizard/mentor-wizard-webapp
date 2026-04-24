<?php

declare(strict_types=1);

use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Enums\CalendarProviderEnum;
use App\Enums\CalendarSyncStatusEnum;
use App\Jobs\CreateExternalCalendarEvent;
use App\Jobs\ProcessCalendarEventExternalCalendarIntegrations;
use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Date;

mutates(ProcessCalendarEventExternalCalendarIntegrations::class);

describe('ProcessCalendarEventExternalCalendarIntegrations job', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->mentor = User::factory()->create();
        $this->mentee = User::factory()->create();
        $this->mentorProgram = MentorProgram::factory()->create(['mentor_id' => $this->mentor->getKey()]);

        $this->event = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => Date::tomorrow()->setTime(10, 0),
            'end_date_time'     => Date::tomorrow()->setTime(11, 0),
            'date'              => Date::tomorrow()->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $this->event->calendarEventUsers()->attach([$this->mentor->getKey(), $this->mentee->getKey()]);

        $this->integration = UserCalendarIntegration::factory()->create([
            'user_id'     => $this->mentor->getKey(),
            'provider'    => CalendarProviderEnum::GOOGLE,
            'sync_status' => CalendarSyncStatusEnum::ACTIVE,
        ]);
    });

    it('dispatches a CreateCalendarEventInExternalCalendar job for each active integration', function (): void {
        Bus::fake();

        new ProcessCalendarEventExternalCalendarIntegrations($this->event)->handle();

        Bus::assertDispatched(fn (CreateExternalCalendarEvent $job): bool => $job->calendarEvent->getKey() === $this->event->getKey()
            && $job->integration->getKey() === $this->integration->getKey());
    });

    it('dispatches one job per active integration across users', function (): void {
        Bus::fake();

        $menteeIntegration = UserCalendarIntegration::factory()->create([
            'user_id'     => $this->mentee->getKey(),
            'provider'    => CalendarProviderEnum::OUTLOOK,
            'sync_status' => CalendarSyncStatusEnum::ACTIVE,
        ]);

        new ProcessCalendarEventExternalCalendarIntegrations($this->event)->handle();

        Bus::assertDispatchedTimes(CreateExternalCalendarEvent::class, 2);
        Bus::assertDispatched(fn (CreateExternalCalendarEvent $job): bool => $job->integration->getKey() === $this->integration->getKey());
        Bus::assertDispatched(fn (CreateExternalCalendarEvent $job): bool => $job->integration->getKey() === $menteeIntegration->getKey());
    });

    it('skips integrations that are not active', function (): void {
        Bus::fake();

        $this->integration->update(['sync_status' => CalendarSyncStatusEnum::ERROR]);

        new ProcessCalendarEventExternalCalendarIntegrations($this->event)->handle();

        Bus::assertNotDispatched(CreateExternalCalendarEvent::class);
    });

    it('does nothing when no users have calendar integrations', function (): void {
        Bus::fake();

        $this->integration->delete();

        new ProcessCalendarEventExternalCalendarIntegrations($this->event)->handle();

        Bus::assertNotDispatched(CreateExternalCalendarEvent::class);
    });
});
