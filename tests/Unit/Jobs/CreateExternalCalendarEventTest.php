<?php

declare(strict_types=1);

use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Enums\CalendarProviderEnum;
use App\Enums\ExternalCalendarEventLogTypeEnum;
use App\Enums\ExternalCalendarEventSyncStatusEnum;
use App\Jobs\CreateExternalCalendarEvent;
use App\Models\CalendarEvent;
use App\Models\ExternalCalendarEvent;
use App\Models\ExternalCalendarEventLog;
use App\Models\MentorProgram;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use App\Services\ExternalCalendar\Contracts\ExternalCalendarServiceInterface;
use App\Services\ExternalCalendar\ExternalCalendarServiceFactory;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;

mutates(CreateExternalCalendarEvent::class);

describe('CreateExternalCalendarEvent job', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        config(['calendar.encryption_key1' => base64_encode(random_bytes(32))]);

        $this->user = User::factory()->create();
        $mentorProgram = MentorProgram::factory()->create(['mentor_id' => $this->user->getKey()]);

        $this->event = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => Date::tomorrow()->setTime(10, 0),
            'end_date_time'     => Date::tomorrow()->setTime(11, 0),
            'date'              => Date::tomorrow()->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'mentor_program_id' => $mentorProgram->getKey(),
        ]);

        $this->integration = UserCalendarIntegration::factory()->create([
            'user_id'  => $this->user->getKey(),
            'provider' => CalendarProviderEnum::GOOGLE,
        ]);
    });

    it('calls createEvent on the resolved external calendar service', function (): void {
        $service = Mockery::mock(ExternalCalendarServiceInterface::class);
        $service->shouldReceive('createEvent')
            ->once()
            ->with(
                Mockery::on(fn ($e): bool => $e->getKey() === $this->event->getKey()),
                Mockery::on(fn ($i): bool => $i->getKey() === $this->integration->getKey()),
            )
            ->andReturn('ext-created-id');

        $factory = Mockery::mock(ExternalCalendarServiceFactory::class);
        $factory->shouldReceive('for')->once()->with($this->integration->provider)->andReturn($service);

        new CreateExternalCalendarEvent($this->event, $this->integration)->handle($factory);
    });

    it('creates an ExternalCalendarEvent with Synced status on success', function (): void {
        $service = Mockery::mock(ExternalCalendarServiceInterface::class);
        $service->shouldReceive('createEvent')->once()->andReturn('ext-evt-synced');

        $factory = Mockery::mock(ExternalCalendarServiceFactory::class);
        $factory->shouldReceive('for')->once()->with($this->integration->provider)->andReturn($service);

        new CreateExternalCalendarEvent($this->event, $this->integration)->handle($factory);

        $this->assertDatabaseHas(ExternalCalendarEvent::class, [
            'calendar_event_id' => $this->event->getKey(),
            'user_id'           => $this->user->getKey(),
            'external_event_id' => 'ext-evt-synced',
            'sync_status'       => ExternalCalendarEventSyncStatusEnum::Synced->value,
        ]);
    });

    it('creates a Success log entry on successful creation', function (): void {
        $service = Mockery::mock(ExternalCalendarServiceInterface::class);
        $service->shouldReceive('createEvent')->once()->andReturn('ext-evt-ok');

        $factory = Mockery::mock(ExternalCalendarServiceFactory::class);
        $factory->shouldReceive('for')->once()->with($this->integration->provider)->andReturn($service);

        new CreateExternalCalendarEvent($this->event, $this->integration)->handle($factory);

        $externalEvent = ExternalCalendarEvent::query()
            ->where('calendar_event_id', $this->event->getKey())
            ->firstOrFail();

        $this->assertDatabaseHas(ExternalCalendarEventLog::class, [
            'external_calendar_event_id' => $externalEvent->getKey(),
            'calendar_event_id'          => $this->event->getKey(),
            'user_id'                    => $this->user->getKey(),
            'type'                       => ExternalCalendarEventLogTypeEnum::Success->value,
        ]);
    });

    it('creates an ExternalCalendarEvent with Error status when service throws', function (): void {
        new CreateExternalCalendarEvent($this->event, $this->integration)
            ->failed(new RuntimeException('Connection timeout'));

        $this->assertDatabaseHas(ExternalCalendarEvent::class, [
            'calendar_event_id' => $this->event->getKey(),
            'user_id'           => $this->user->getKey(),
            'sync_status'       => ExternalCalendarEventSyncStatusEnum::Error->value,
        ]);
    });

    it('creates an Error log entry with the exception message when service throws', function (): void {
        new CreateExternalCalendarEvent($this->event, $this->integration)
            ->failed(new RuntimeException('API quota exceeded'));

        $externalEvent = ExternalCalendarEvent::query()
            ->where('calendar_event_id', $this->event->getKey())
            ->firstOrFail();

        $this->assertDatabaseHas(ExternalCalendarEventLog::class, [
            'external_calendar_event_id' => $externalEvent->getKey(),
            'type'                       => ExternalCalendarEventLogTypeEnum::Error->value,
            'message'                    => 'API quota exceeded',
        ]);
    });
});
