<?php

declare(strict_types=1);

use App\Models\MentorProgram;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Queue;
use Modules\Calendar\Enums\CalendarEventStatusEnum;
use Modules\Calendar\Enums\CalendarEventTypeEnum;
use Modules\Calendar\Events\CalendarEventCancelled;
use Modules\Calendar\Events\CalendarEventConfirmed;
use Modules\Calendar\Events\CalendarEventContentChanged;
use Modules\Calendar\Events\CalendarEventDeleting;
use Modules\Calendar\Models\CalendarEvent;
use Modules\ExternalCalendar\Jobs\ProcessCalendarEventExternalCalendarIntegrations;
use Modules\ExternalCalendar\Jobs\ProcessDeleteExternalCalendarEvent;
use Modules\ExternalCalendar\Jobs\ProcessUpdateExternalCalendarEvent;
use Modules\ExternalCalendar\Listeners\SyncExternalCalendarOnCalendarEventChange;

mutates(SyncExternalCalendarOnCalendarEventChange::class);

describe('SyncExternalCalendarOnCalendarEventChange', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        $mentor = User::factory()->create();
        $mentorProgram = MentorProgram::factory()->create(['mentor_id' => $mentor->getKey()]);

        $this->event = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => Date::tomorrow()->setTime(10, 0),
            'end_date_time'     => Date::tomorrow()->setTime(11, 0),
            'date'              => Date::tomorrow()->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'mentor_program_id' => $mentorProgram->getKey(),
        ]);

        $this->listener = new SyncExternalCalendarOnCalendarEventChange;
    });

    it('queues ProcessCalendarEventExternalCalendarIntegrations on handleConfirmed', function (): void {
        Queue::fake();

        $this->listener->handleConfirmed(new CalendarEventConfirmed($this->event));

        Queue::assertPushed(
            ProcessCalendarEventExternalCalendarIntegrations::class,
            fn ($job): bool => $job->calendarEvent->getKey() === $this->event->getKey()
        );
    });

    it('queues ProcessDeleteExternalCalendarEvent on handleCancelled', function (): void {
        Queue::fake();

        $this->listener->handleCancelled(new CalendarEventCancelled($this->event->getKey()));

        Queue::assertPushed(
            ProcessDeleteExternalCalendarEvent::class,
            fn ($job): bool => $job->calendarEventId === $this->event->getKey()
        );
    });

    it('queues ProcessUpdateExternalCalendarEvent on handleContentChanged', function (): void {
        Queue::fake();

        $this->listener->handleContentChanged(new CalendarEventContentChanged($this->event));

        Queue::assertPushed(
            ProcessUpdateExternalCalendarEvent::class,
            fn ($job): bool => $job->calendarEvent->getKey() === $this->event->getKey()
        );
    });

    it('queues ProcessDeleteExternalCalendarEvent on handleDeleting', function (): void {
        Queue::fake();

        $this->listener->handleDeleting(new CalendarEventDeleting($this->event->getKey()));

        Queue::assertPushed(
            ProcessDeleteExternalCalendarEvent::class,
            fn ($job): bool => $job->calendarEventId === $this->event->getKey()
        );
    });
});
