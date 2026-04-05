<?php

declare(strict_types=1);

use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Enums\CalendarProviderEnum;
use App\Enums\CalendarSyncStatusEnum;
use App\Jobs\DeleteExternalCalendarEvent;
use App\Models\CalendarEvent;
use App\Models\ExternalCalendarEvent;
use App\Models\MentorProgram;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use App\Services\ExternalCalendar\ExternalCalendarServiceInterface;
use Illuminate\Support\Facades\Date;

mutates(DeleteExternalCalendarEvent::class);

describe('DeleteExternalCalendarEvent job', function (): void {
    beforeEach(function (): void {
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

    it('calls deleteEvent on the external calendar service and removes the record', function (): void {
        $service = Mockery::mock(ExternalCalendarServiceInterface::class);
        $service->shouldReceive('deleteEvent')
            ->once()
            ->with(
                Mockery::on(fn ($i) => $i->getKey() === $this->integration->getKey()),
                'ext-event-456',
            );

        app()->instance($this->integration->provider->serviceClass(), $service);

        (new DeleteExternalCalendarEvent($this->event->getKey()))->handle();

        expect(ExternalCalendarEvent::query()->find($this->externalEvent->getKey()))->toBeNull();
    });

    it('deletes the ExternalCalendarEvent record when integration no longer exists', function (): void {
        $this->integration->delete();

        (new DeleteExternalCalendarEvent($this->event->getKey()))->handle();

        expect(ExternalCalendarEvent::query()->find($this->externalEvent->getKey()))->toBeNull();
    });

    it('marks integration as error when deletion fails and keeps the record', function (): void {
        $service = Mockery::mock(ExternalCalendarServiceInterface::class);
        $service->shouldReceive('deleteEvent')
            ->once()
            ->andThrow(new RuntimeException('Google API error'));

        app()->instance($this->integration->provider->serviceClass(), $service);

        (new DeleteExternalCalendarEvent($this->event->getKey()))->handle();

        expect($this->integration->refresh()->sync_status)->toBe(CalendarSyncStatusEnum::Error)
            ->and($this->integration->refresh()->last_error_message)->toBe('Google API error');

        expect(ExternalCalendarEvent::query()->find($this->externalEvent->getKey()))->not->toBeNull();
    });

    it('does nothing when no external events exist', function (): void {
        $this->externalEvent->delete();

        $service = Mockery::mock(ExternalCalendarServiceInterface::class);
        $service->shouldNotReceive('deleteEvent');

        app()->instance($this->integration->provider->serviceClass(), $service);

        (new DeleteExternalCalendarEvent($this->event->getKey()))->handle();
    });
});
