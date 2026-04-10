<?php

declare(strict_types=1);

use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Enums\CalendarProviderEnum;
use App\Enums\CalendarSyncStatusEnum;
use App\Jobs\SyncCalendarEventToExternalCalendar;
use App\Models\CalendarEvent;
use App\Models\ExternalCalendarEvent;
use App\Models\MentorProgram;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use App\Services\ExternalCalendar\ExternalCalendarServiceInterface;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;

mutates(SyncCalendarEventToExternalCalendar::class);

describe('SyncCalendarEventToExternalCalendar job', function (): void {
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
            'provider'    => CalendarProviderEnum::Google,
            'sync_status' => CalendarSyncStatusEnum::Active,
        ]);
    });

    it('creates an external event and stores the mapping record', function (): void {
        $service = Mockery::mock(ExternalCalendarServiceInterface::class);
        $service->shouldReceive('createEvent')
            ->once()
            ->with(
                Mockery::on(fn ($e): bool => $e->getKey() === $this->event->getKey()),
                Mockery::on(fn ($i): bool => $i->getKey() === $this->integration->getKey()),
            )
            ->andReturn('ext-new-event-id');

        app()->instance($this->integration->provider->getService(), $service);

        new SyncCalendarEventToExternalCalendar($this->event)->handle();

        $externalEvent = ExternalCalendarEvent::query()
            ->where('calendar_event_id', $this->event->getKey())
            ->where('user_id', $this->mentor->getKey())
            ->first();

        expect($externalEvent)->not->toBeNull()
            ->and($externalEvent->external_event_id)->toBe('ext-new-event-id')
            ->and($externalEvent->provider)->toBe(CalendarProviderEnum::Google);
    });

    it('syncs to multiple integrations for different users', function (): void {
        $menteeIntegration = UserCalendarIntegration::factory()->create([
            'user_id'     => $this->mentee->getKey(),
            'provider'    => CalendarProviderEnum::Outlook,
            'sync_status' => CalendarSyncStatusEnum::Active,
        ]);

        $googleService = Mockery::mock(ExternalCalendarServiceInterface::class);
        $googleService->shouldReceive('createEvent')
            ->once()
            ->andReturn('google-ext-id');

        $outlookService = Mockery::mock(ExternalCalendarServiceInterface::class);
        $outlookService->shouldReceive('createEvent')
            ->once()
            ->andReturn('outlook-ext-id');

        app()->instance(CalendarProviderEnum::Google->getService(), $googleService);
        app()->instance(CalendarProviderEnum::Outlook->getService(), $outlookService);

        new SyncCalendarEventToExternalCalendar($this->event)->handle();

        expect(ExternalCalendarEvent::query()->where('calendar_event_id', $this->event->getKey())->count())->toBe(2);
    });

    it('skips integrations that are not active', function (): void {
        $this->integration->update(['sync_status' => CalendarSyncStatusEnum::Error]);

        $service = Mockery::mock(ExternalCalendarServiceInterface::class);
        $service->shouldNotReceive('createEvent');

        app()->instance($this->integration->provider->getService(), $service);

        new SyncCalendarEventToExternalCalendar($this->event)->handle();

        expect(ExternalCalendarEvent::query()->where('calendar_event_id', $this->event->getKey())->count())->toBe(0);
    });

    it('marks integration as error when creation fails', function (): void {
        $service = Mockery::mock(ExternalCalendarServiceInterface::class);
        $service->shouldReceive('createEvent')
            ->once()
            ->andThrow(new RuntimeException('API rate limit exceeded'));

        app()->instance($this->integration->provider->getService(), $service);

        new SyncCalendarEventToExternalCalendar($this->event)->handle();

        expect($this->integration->refresh()->sync_status)->toBe(CalendarSyncStatusEnum::Error)
            ->and($this->integration->refresh()->last_error_message)->toBe('API rate limit exceeded');
    });

    it('does nothing when no users have calendar integrations', function (): void {
        $this->integration->delete();

        new SyncCalendarEventToExternalCalendar($this->event)->handle();

        expect(ExternalCalendarEvent::query()->where('calendar_event_id', $this->event->getKey())->count())->toBe(0);
    });
});
