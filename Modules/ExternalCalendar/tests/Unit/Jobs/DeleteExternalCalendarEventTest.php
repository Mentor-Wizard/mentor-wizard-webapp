<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;
use Modules\Calendar\Enums\CalendarEventStatusEnum;
use Modules\Calendar\Enums\CalendarEventTypeEnum;
use Modules\Calendar\Models\CalendarEvent;
use Modules\ExternalCalendar\Enums\CalendarProviderEnum;
use Modules\ExternalCalendar\Enums\CalendarSyncStatusEnum;
use Modules\ExternalCalendar\Enums\ExternalCalendarEventLogTypeEnum;
use Modules\ExternalCalendar\Jobs\DeleteExternalCalendarEvent;
use Modules\ExternalCalendar\Models\ExternalCalendarEvent;
use Modules\ExternalCalendar\Models\ExternalCalendarEventLog;
use Modules\ExternalCalendar\Models\UserCalendarIntegration;
use Modules\ExternalCalendar\Services\Contracts\ExternalCalendarServiceInterface;
use Modules\ExternalCalendar\Services\ExternalCalendarServiceFactory;
use Modules\MentorProgram\Models\MentorProgram;

mutates(DeleteExternalCalendarEvent::class);

describe('DeleteExternalCalendarEvent job', function (): void {
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
            'external_event_id' => 'ext-event-456',
        ]);
    });

    it('calls deleteEvent on the external calendar service and removes the record', function (): void {
        $service = Mockery::mock(ExternalCalendarServiceInterface::class);
        $service->shouldReceive('deleteEvent')
            ->once()
            ->with(
                Mockery::on(fn ($i): bool => $i->getKey() === $this->integration->getKey()),
                'ext-event-456',
            );

        $factory = Mockery::mock(ExternalCalendarServiceFactory::class);
        $factory->shouldReceive('for')->once()->with($this->integration->provider)->andReturn($service);

        new DeleteExternalCalendarEvent($this->externalEvent, $this->integration)->handle($factory);

        expect(ExternalCalendarEvent::query()->find($this->externalEvent->getKey()))->toBeNull();
    });

    it('creates a success ExternalCalendarEventLog when delete succeeds', function (): void {
        $service = Mockery::mock(ExternalCalendarServiceInterface::class);
        $service->shouldReceive('deleteEvent')->once();

        $factory = Mockery::mock(ExternalCalendarServiceFactory::class);
        $factory->shouldReceive('for')->once()->with($this->integration->provider)->andReturn($service);

        new DeleteExternalCalendarEvent($this->externalEvent, $this->integration)->handle($factory);

        $this->assertDatabaseHas(ExternalCalendarEventLog::class, [
            'calendar_event_id' => $this->event->getKey(),
            'user_id'           => $this->user->getKey(),
            'type'              => ExternalCalendarEventLogTypeEnum::Success->value,
        ]);
    });

    it('creates an error ExternalCalendarEventLog when delete fails', function (): void {
        new DeleteExternalCalendarEvent($this->externalEvent, $this->integration)
            ->failed(new RuntimeException('API rate limit exceeded'));

        $this->assertDatabaseHas(ExternalCalendarEventLog::class, [
            'external_calendar_event_id' => $this->externalEvent->getKey(),
            'calendar_event_id'          => $this->event->getKey(),
            'user_id'                    => $this->user->getKey(),
            'type'                       => ExternalCalendarEventLogTypeEnum::Error->value,
            'message'                    => 'API rate limit exceeded',
        ]);
    });

    it('deletes the ExternalCalendarEvent record when integration is null', function (): void {
        $factory = Mockery::mock(ExternalCalendarServiceFactory::class);

        new DeleteExternalCalendarEvent($this->externalEvent, null)->handle($factory);

        expect(ExternalCalendarEvent::query()->find($this->externalEvent->getKey()))->toBeNull();
    });

    it('creates an info ExternalCalendarEventLog when integration is null', function (): void {
        $factory = Mockery::mock(ExternalCalendarServiceFactory::class);

        new DeleteExternalCalendarEvent($this->externalEvent, null)->handle($factory);

        $this->assertDatabaseHas(ExternalCalendarEventLog::class, [
            'calendar_event_id' => $this->event->getKey(),
            'user_id'           => $this->user->getKey(),
            'type'              => ExternalCalendarEventLogTypeEnum::Info->value,
            'message'           => 'No active integration found. Local record removed.',
        ]);
    });
});
