<?php

declare(strict_types=1);

use App\Enums\CalendarEventColoursEnum;
use App\Enums\CalendarEventRoleEnum;
use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Enums\RoleEnum;
use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use App\Models\MentorSession;
use App\Models\User;
use App\Observers\CalendarEventObserver;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;
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
