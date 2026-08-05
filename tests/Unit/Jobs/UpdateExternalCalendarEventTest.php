<?php

declare(strict_types=1);

use App\Enums\CalendarProviderEnum;
use App\Enums\CalendarSyncStatusEnum;
use App\Enums\ExternalCalendarEventLogTypeEnum;
use App\Jobs\UpdateExternalCalendarEvent;
use App\Models\ExternalCalendarEvent;
use App\Models\ExternalCalendarEventLog;
use App\Models\MentorProgram;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use App\Services\ExternalCalendar\Contracts\ExternalCalendarServiceInterface;
use App\Services\ExternalCalendar\ExternalCalendarServiceFactory;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;
use Modules\Calendar\Enums\CalendarEventStatusEnum;
use Modules\Calendar\Enums\CalendarEventTypeEnum;
use Modules\Calendar\Models\CalendarEvent;

mutates(UpdateExternalCalendarEvent::class);

describe('UpdateExternalCalendarEvent job', function (): void {
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

    it('calls updateEvent on the external calendar service', function (): void {
        $service = Mockery::mock(ExternalCalendarServiceInterface::class);
        $service->shouldReceive('updateEvent')
            ->once()
            ->with(
                Mockery::on(fn ($e): bool => $e->getKey() === $this->event->getKey()),
                Mockery::on(fn ($i): bool => $i->getKey() === $this->integration->getKey()),
                'ext-event-123',
            );

        $factory = Mockery::mock(ExternalCalendarServiceFactory::class);
        $factory->shouldReceive('for')->once()->with($this->integration->provider)->andReturn($service);

        new UpdateExternalCalendarEvent($this->event, $this->externalEvent, $this->integration)->handle($factory);
    });

    it('creates a success ExternalCalendarEventLog when update succeeds', function (): void {
        $service = Mockery::mock(ExternalCalendarServiceInterface::class);
        $service->shouldReceive('updateEvent')->once();

        $factory = Mockery::mock(ExternalCalendarServiceFactory::class);
        $factory->shouldReceive('for')->once()->with($this->integration->provider)->andReturn($service);

        new UpdateExternalCalendarEvent($this->event, $this->externalEvent, $this->integration)->handle($factory);

        $this->assertDatabaseHas(ExternalCalendarEventLog::class, [
            'external_calendar_event_id' => $this->externalEvent->getKey(),
            'calendar_event_id'          => $this->event->getKey(),
            'user_id'                    => $this->user->getKey(),
            'type'                       => ExternalCalendarEventLogTypeEnum::Success->value,
        ]);
    });

    it('creates an error ExternalCalendarEventLog when update fails', function (): void {
        new UpdateExternalCalendarEvent($this->event, $this->externalEvent, $this->integration)
            ->failed(new RuntimeException('Connection timeout'));

        $this->assertDatabaseHas(ExternalCalendarEventLog::class, [
            'external_calendar_event_id' => $this->externalEvent->getKey(),
            'calendar_event_id'          => $this->event->getKey(),
            'user_id'                    => $this->user->getKey(),
            'type'                       => ExternalCalendarEventLogTypeEnum::Error->value,
            'message'                    => 'Connection timeout',
        ]);
    });
});
