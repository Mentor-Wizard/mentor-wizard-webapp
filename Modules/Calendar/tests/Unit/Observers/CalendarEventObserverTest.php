<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Modules\Calendar\Enums\CalendarEventColoursEnum;
use Modules\Calendar\Enums\CalendarEventRoleEnum;
use Modules\Calendar\Enums\CalendarEventStatusEnum;
use Modules\Calendar\Enums\CalendarEventTypeEnum;
use Modules\Calendar\Events\CalendarEventCancelled;
use Modules\Calendar\Events\CalendarEventConfirmed;
use Modules\Calendar\Events\CalendarEventContentChanged;
use Modules\Calendar\Events\CalendarEventDeleting;
use Modules\Calendar\Models\CalendarEvent;
use Modules\Calendar\Observers\CalendarEventObserver;
use Modules\ExternalCalendar\Jobs\ProcessCalendarEventExternalCalendarIntegrations;
use Modules\ExternalCalendar\Jobs\ProcessDeleteExternalCalendarEvent;
use Modules\ExternalCalendar\Jobs\ProcessUpdateExternalCalendarEvent;
use Modules\MentorProgram\Models\MentorProgram;
use Modules\MentorSession\Models\MentorSession;
use Spatie\Permission\Models\Role;

mutates(CalendarEventObserver::class);

describe('CalendarEventObserver', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        $this->mentor = User::factory()->create();
        $this->mentor->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $this->mentee = User::factory()->create();

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
        ]);
    });

    describe('MentorSession creation on status change to CONFIRMED', function (): void {
        it('creates MentorSession when event status changes to CONFIRMED', function (): void {
            $event = CalendarEvent::factory()->create([
                'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION->value,
                'start_date_time'   => Date::tomorrow()->setTime(10, 0, 0),
                'end_date_time'     => Date::tomorrow()->setTime(11, 0, 0),
                'date'              => Date::tomorrow()->format('Y-m-d'),
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ]);

            // Attach users with roles
            $event->calendarEventUsers()->attach($this->mentor->getKey(), [
                'role'   => CalendarEventRoleEnum::HOST->value,
                'colour' => CalendarEventColoursEnum::BLUE->value,
            ]);
            $event->calendarEventUsers()->attach($this->mentee->getKey(), [
                'role'   => CalendarEventRoleEnum::PARTICIPANT->value,
                'colour' => CalendarEventColoursEnum::GREEN->value,
            ]);

            // Verify no MentorSession exists yet
            expect(MentorSession::query()->count())->toBe(0);

            // Update status to CONFIRMED
            $event->update(['status' => CalendarEventStatusEnum::CONFIRMED->value]);

            // Verify MentorSession was created
            expect(MentorSession::query()->count())->toBe(1);

            $session = MentorSession::query()->first();
            expect($session->mentor_id)->toBe($this->mentor->getKey())
                ->and($session->menti_id)->toBe($this->mentee->getKey())
                ->and($session->mentor_program_id)->toBe($this->mentorProgram->getKey());
        });

        it('does not create MentorSession when status does not change', function (): void {
            $event = CalendarEvent::factory()->create([
                'status'            => CalendarEventStatusEnum::CONFIRMED->value,
                'start_date_time'   => Date::tomorrow()->setTime(10, 0, 0),
                'end_date_time'     => Date::tomorrow()->setTime(11, 0, 0),
                'date'              => Date::tomorrow()->format('Y-m-d'),
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ]);

            $event->calendarEventUsers()->attach($this->mentor->getKey(), [
                'role'   => CalendarEventRoleEnum::HOST->value,
                'colour' => CalendarEventColoursEnum::BLUE->value,
            ]);
            $event->calendarEventUsers()->attach($this->mentee->getKey(), [
                'role'   => CalendarEventRoleEnum::PARTICIPANT->value,
                'colour' => CalendarEventColoursEnum::GREEN->value,
            ]);

            $initialSessionCount = MentorSession::query()->count();

            // Update a different field (not status)
            $event->update(['title' => 'Updated Title']);

            expect(MentorSession::query()->count())->toBe($initialSessionCount);
        });

        it('does not create MentorSession when status changes to non-CONFIRMED', function (): void {
            $event = CalendarEvent::factory()->create([
                'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION->value,
                'start_date_time'   => Date::tomorrow()->setTime(10, 0, 0),
                'end_date_time'     => Date::tomorrow()->setTime(11, 0, 0),
                'date'              => Date::tomorrow()->format('Y-m-d'),
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ]);

            $event->calendarEventUsers()->attach($this->mentor->getKey(), [
                'role'   => CalendarEventRoleEnum::HOST->value,
                'colour' => CalendarEventColoursEnum::BLUE->value,
            ]);
            $event->calendarEventUsers()->attach($this->mentee->getKey(), [
                'role'   => CalendarEventRoleEnum::PARTICIPANT->value,
                'colour' => CalendarEventColoursEnum::GREEN->value,
            ]);

            // Update status to CANCELLED (not CONFIRMED)
            $event->update(['status' => CalendarEventStatusEnum::CANCELLED->value]);

            expect(MentorSession::query()->count())->toBe(0);
        });

        it('does not create MentorSession when event has no mentor_program_id', function (): void {
            $event = CalendarEvent::factory()->create([
                'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION->value,
                'start_date_time'   => Date::tomorrow()->setTime(10, 0, 0),
                'end_date_time'     => Date::tomorrow()->setTime(11, 0, 0),
                'date'              => Date::tomorrow()->format('Y-m-d'),
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'mentor_program_id' => null,
            ]);

            $event->calendarEventUsers()->attach($this->mentor->getKey(), [
                'role'   => CalendarEventRoleEnum::HOST->value,
                'colour' => CalendarEventColoursEnum::BLUE->value,
            ]);
            $event->calendarEventUsers()->attach($this->mentee->getKey(), [
                'role'   => CalendarEventRoleEnum::PARTICIPANT->value,
                'colour' => CalendarEventColoursEnum::GREEN->value,
            ]);

            // Update status to CONFIRMED
            $event->update(['status' => CalendarEventStatusEnum::CONFIRMED->value]);

            expect(MentorSession::query()->count())->toBe(0);
        });

        it('does not create MentorSession when HOST user is missing', function (): void {
            $event = CalendarEvent::factory()->create([
                'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION->value,
                'start_date_time'   => Date::tomorrow()->setTime(10, 0, 0),
                'end_date_time'     => Date::tomorrow()->setTime(11, 0, 0),
                'date'              => Date::tomorrow()->format('Y-m-d'),
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ]);

            // Only attach participant (no HOST)
            $event->calendarEventUsers()->attach($this->mentee->getKey(), [
                'role'   => CalendarEventRoleEnum::PARTICIPANT->value,
                'colour' => CalendarEventColoursEnum::GREEN->value,
            ]);

            // Update status to CONFIRMED
            $event->update(['status' => CalendarEventStatusEnum::CONFIRMED->value]);

            expect(MentorSession::query()->count())->toBe(0);
        });

        it('does not create MentorSession when PARTICIPANT user is missing', function (): void {
            $event = CalendarEvent::factory()->create([
                'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION->value,
                'start_date_time'   => Date::tomorrow()->setTime(10, 0, 0),
                'end_date_time'     => Date::tomorrow()->setTime(11, 0, 0),
                'date'              => Date::tomorrow()->format('Y-m-d'),
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ]);

            // Only attach host (no PARTICIPANT)
            $event->calendarEventUsers()->attach($this->mentor->getKey(), [
                'role'   => CalendarEventRoleEnum::HOST->value,
                'colour' => CalendarEventColoursEnum::BLUE->value,
            ]);

            // Update status to CONFIRMED
            $event->update(['status' => CalendarEventStatusEnum::CONFIRMED->value]);

            expect(MentorSession::query()->count())->toBe(0);
        });
    });

    describe('External calendar domain events', function (): void {
        it('dispatches CalendarEventConfirmed when created with CONFIRMED status', function (): void {
            Event::fake([CalendarEventConfirmed::class]);

            CalendarEvent::factory()->create([
                'status'            => CalendarEventStatusEnum::CONFIRMED,
                'start_date_time'   => Date::tomorrow()->setTime(10, 0),
                'end_date_time'     => Date::tomorrow()->setTime(11, 0),
                'date'              => Date::tomorrow()->format('Y-m-d'),
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ]);

            Event::assertDispatched(CalendarEventConfirmed::class);
        });

        it('does not dispatch CalendarEventConfirmed when created with non-CONFIRMED status', function (): void {
            Event::fake([CalendarEventConfirmed::class]);

            CalendarEvent::factory()->create([
                'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION,
                'start_date_time'   => Date::tomorrow()->setTime(10, 0),
                'end_date_time'     => Date::tomorrow()->setTime(11, 0),
                'date'              => Date::tomorrow()->format('Y-m-d'),
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ]);

            Event::assertNotDispatched(CalendarEventConfirmed::class);
        });

        it('dispatches CalendarEventConfirmed when status changes to CONFIRMED', function (): void {
            Event::fake([CalendarEventConfirmed::class]);

            $event = CalendarEvent::factory()->create([
                'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION,
                'start_date_time'   => Date::tomorrow()->setTime(10, 0),
                'end_date_time'     => Date::tomorrow()->setTime(11, 0),
                'date'              => Date::tomorrow()->format('Y-m-d'),
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ]);

            $event->update(['status' => CalendarEventStatusEnum::CONFIRMED]);

            Event::assertDispatched(CalendarEventConfirmed::class);
        });

        it('dispatches CalendarEventCancelled when status changes to CANCELLED', function (): void {
            Event::fake([CalendarEventCancelled::class]);

            $event = CalendarEvent::factory()->create([
                'status'            => CalendarEventStatusEnum::CONFIRMED,
                'start_date_time'   => Date::tomorrow()->setTime(10, 0),
                'end_date_time'     => Date::tomorrow()->setTime(11, 0),
                'date'              => Date::tomorrow()->format('Y-m-d'),
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ]);

            $event->update(['status' => CalendarEventStatusEnum::CANCELLED]);

            Event::assertDispatched(fn (CalendarEventCancelled $e): bool => $e->calendarEventId === $event->getKey());
        });

        it('dispatches CalendarEventContentChanged when CONFIRMED event content fields change', function (): void {
            Event::fake([CalendarEventContentChanged::class]);

            $event = CalendarEvent::factory()->create([
                'status'            => CalendarEventStatusEnum::CONFIRMED,
                'start_date_time'   => Date::tomorrow()->setTime(10, 0),
                'end_date_time'     => Date::tomorrow()->setTime(11, 0),
                'date'              => Date::tomorrow()->format('Y-m-d'),
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ]);

            $event->update(['title' => 'Updated title']);

            Event::assertDispatched(fn (CalendarEventContentChanged $e): bool => $e->calendarEvent->getKey() === $event->getKey());
        });

        it('does not dispatch CalendarEventContentChanged when non-content fields change', function (): void {
            $event = CalendarEvent::factory()->create([
                'status'            => CalendarEventStatusEnum::CONFIRMED,
                'start_date_time'   => Date::tomorrow()->setTime(10, 0),
                'end_date_time'     => Date::tomorrow()->setTime(11, 0),
                'date'              => Date::tomorrow()->format('Y-m-d'),
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
                'web_link'          => null,
            ]);

            Event::fake([CalendarEventContentChanged::class]);

            $event->update(['web_link' => null]); // no actual change — already null
            $event->update(['type' => CalendarEventTypeEnum::GROUP->value]); // type is not a CONTENT_FIELD

            Event::assertNotDispatched(CalendarEventContentChanged::class);
        });

        it('dispatches CalendarEventDeleting when event is being deleted', function (): void {
            $event = CalendarEvent::factory()->create([
                'status'            => CalendarEventStatusEnum::CANCELLED,
                'start_date_time'   => Date::tomorrow()->setTime(10, 0),
                'end_date_time'     => Date::tomorrow()->setTime(11, 0),
                'date'              => Date::tomorrow()->format('Y-m-d'),
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ]);

            $eventId = $event->getKey();

            Event::fake([CalendarEventDeleting::class]);

            $event->delete();

            Event::assertDispatched(fn (CalendarEventDeleting $e): bool => $e->calendarEventId === $eventId);
        });
    });

    describe('External calendar sync end to end (Observer → Event → Listener → Job)', function (): void {
        it('queues ProcessCalendarEventExternalCalendarIntegrations when an event is confirmed', function (): void {
            Queue::fake();

            $event = CalendarEvent::factory()->create([
                'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION,
                'start_date_time'   => Date::tomorrow()->setTime(10, 0),
                'end_date_time'     => Date::tomorrow()->setTime(11, 0),
                'date'              => Date::tomorrow()->format('Y-m-d'),
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ]);

            $event->update(['status' => CalendarEventStatusEnum::CONFIRMED]);

            Queue::assertPushed(ProcessCalendarEventExternalCalendarIntegrations::class);
        });

        it('queues ProcessDeleteExternalCalendarEvent when an event is cancelled', function (): void {
            Queue::fake();

            $event = CalendarEvent::factory()->create([
                'status'            => CalendarEventStatusEnum::CONFIRMED,
                'start_date_time'   => Date::tomorrow()->setTime(10, 0),
                'end_date_time'     => Date::tomorrow()->setTime(11, 0),
                'date'              => Date::tomorrow()->format('Y-m-d'),
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ]);

            $event->update(['status' => CalendarEventStatusEnum::CANCELLED]);

            Queue::assertPushed(ProcessDeleteExternalCalendarEvent::class, fn ($job): bool => $job->calendarEventId === $event->getKey());
        });

        it('queues ProcessUpdateExternalCalendarEvent when a confirmed event content field changes', function (): void {
            $event = CalendarEvent::factory()->create([
                'status'            => CalendarEventStatusEnum::CONFIRMED,
                'start_date_time'   => Date::tomorrow()->setTime(10, 0),
                'end_date_time'     => Date::tomorrow()->setTime(11, 0),
                'date'              => Date::tomorrow()->format('Y-m-d'),
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ]);

            Queue::fake();

            $event->update(['title' => 'Updated title']);

            Queue::assertPushed(ProcessUpdateExternalCalendarEvent::class, fn ($job): bool => $job->calendarEvent->getKey() === $event->getKey());
        });

        it('queues ProcessDeleteExternalCalendarEvent when an event is deleted', function (): void {
            $event = CalendarEvent::factory()->create([
                'status'            => CalendarEventStatusEnum::CANCELLED,
                'start_date_time'   => Date::tomorrow()->setTime(10, 0),
                'end_date_time'     => Date::tomorrow()->setTime(11, 0),
                'date'              => Date::tomorrow()->format('Y-m-d'),
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ]);

            $eventId = $event->getKey();

            Queue::fake();

            $event->delete();

            Queue::assertPushed(ProcessDeleteExternalCalendarEvent::class, fn ($job): bool => $job->calendarEventId === $eventId);
        });
    });

    describe('MentorSession data correctness', function (): void {
        it('creates MentorSession with correct date from event start_date_time', function (): void {
            $eventStartTime = Date::tomorrow()->setTime(14, 30, 0);

            $event = CalendarEvent::factory()->create([
                'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION->value,
                'start_date_time'   => $eventStartTime,
                'end_date_time'     => (clone $eventStartTime)->addHour(),
                'date'              => $eventStartTime->format('Y-m-d'),
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ]);

            $event->calendarEventUsers()->attach($this->mentor->getKey(), [
                'role'   => CalendarEventRoleEnum::HOST->value,
                'colour' => CalendarEventColoursEnum::BLUE->value,
            ]);
            $event->calendarEventUsers()->attach($this->mentee->getKey(), [
                'role'   => CalendarEventRoleEnum::PARTICIPANT->value,
                'colour' => CalendarEventColoursEnum::GREEN->value,
            ]);

            $event->update(['status' => CalendarEventStatusEnum::CONFIRMED->value]);

            $session = MentorSession::query()->first();
            expect($session->date->format('Y-m-d H:i:s'))
                ->toBe($eventStartTime->format('Y-m-d H:i:s'));
        });

        it('sets correct mentor_id from HOST role user', function (): void {
            $event = CalendarEvent::factory()->create([
                'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION->value,
                'start_date_time'   => Date::tomorrow()->setTime(10, 0, 0),
                'end_date_time'     => Date::tomorrow()->setTime(11, 0, 0),
                'date'              => Date::tomorrow()->format('Y-m-d'),
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ]);

            $event->calendarEventUsers()->attach($this->mentor->getKey(), [
                'role'   => CalendarEventRoleEnum::HOST->value,
                'colour' => CalendarEventColoursEnum::BLUE->value,
            ]);
            $event->calendarEventUsers()->attach($this->mentee->getKey(), [
                'role'   => CalendarEventRoleEnum::PARTICIPANT->value,
                'colour' => CalendarEventColoursEnum::GREEN->value,
            ]);

            $event->update(['status' => CalendarEventStatusEnum::CONFIRMED->value]);

            $session = MentorSession::query()->first();
            expect($session->mentor_id)->toBe($this->mentor->getKey());
        });

        it('sets correct menti_id from PARTICIPANT role user', function (): void {
            $event = CalendarEvent::factory()->create([
                'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION->value,
                'start_date_time'   => Date::tomorrow()->setTime(10, 0, 0),
                'end_date_time'     => Date::tomorrow()->setTime(11, 0, 0),
                'date'              => Date::tomorrow()->format('Y-m-d'),
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ]);

            $event->calendarEventUsers()->attach($this->mentor->getKey(), [
                'role'   => CalendarEventRoleEnum::HOST->value,
                'colour' => CalendarEventColoursEnum::BLUE->value,
            ]);
            $event->calendarEventUsers()->attach($this->mentee->getKey(), [
                'role'   => CalendarEventRoleEnum::PARTICIPANT->value,
                'colour' => CalendarEventColoursEnum::GREEN->value,
            ]);

            $event->update(['status' => CalendarEventStatusEnum::CONFIRMED->value]);

            $session = MentorSession::query()->first();
            expect($session->menti_id)->toBe($this->mentee->getKey());
        });
    });
});
