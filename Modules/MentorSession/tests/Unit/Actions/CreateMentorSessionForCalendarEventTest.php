<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;
use Modules\Calendar\Enums\CalendarEventColoursEnum;
use Modules\Calendar\Enums\CalendarEventRoleEnum;
use Modules\Calendar\Enums\CalendarEventStatusEnum;
use Modules\Calendar\Enums\CalendarEventTypeEnum;
use Modules\Calendar\Models\CalendarEvent;
use Modules\MentorProgram\Models\MentorProgram;
use Modules\MentorSession\Actions\CreateMentorSessionForCalendarEvent;
use Modules\MentorSession\Models\MentorSession;
use Spatie\Permission\Models\Role;

mutates(CreateMentorSessionForCalendarEvent::class);

describe('CreateMentorSessionForCalendarEvent', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        $this->mentor = User::factory()->create();
        $this->mentor->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $this->mentee = User::factory()->create();
        $this->mentee->assignRole(Role::findByName(RoleEnum::MENTI->value));

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
        ]);
    });

    it('creates MentorSession and sets mentor_session_id on event when CONFIRMED with host and participant', function (): void {
        $startTime = Date::tomorrow()->setTime(10, 0, 0);

        $event = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $startTime,
            'end_date_time'     => (clone $startTime)->addHour(),
            'date'              => $startTime->format('Y-m-d'),
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

        CreateMentorSessionForCalendarEvent::run($event);

        expect(MentorSession::query()->count())->toBe(1);

        $session = MentorSession::query()->first();
        expect($session->mentor_id)->toBe($this->mentor->getKey())
            ->and($session->menti_id)->toBe($this->mentee->getKey())
            ->and($session->mentor_program_id)->toBe($this->mentorProgram->getKey())
            ->and($session->cost)->toBe($this->mentorProgram->cost)
            ->and($event->fresh()->mentor_session_id)->toBe($session->getKey());
    });

    it('does not create MentorSession when status is not CONFIRMED', function (): void {
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

        CreateMentorSessionForCalendarEvent::run($event);

        expect(MentorSession::query()->count())->toBe(0);
    });

    it('does not create MentorSession when event has no mentor_program_id', function (): void {
        $event = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
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

        CreateMentorSessionForCalendarEvent::run($event);

        expect(MentorSession::query()->count())->toBe(0);
    });

    it('does not create MentorSession when HOST user is missing', function (): void {
        $event = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => Date::tomorrow()->setTime(10, 0, 0),
            'end_date_time'     => Date::tomorrow()->setTime(11, 0, 0),
            'date'              => Date::tomorrow()->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $event->calendarEventUsers()->attach($this->mentee->getKey(), [
            'role'   => CalendarEventRoleEnum::PARTICIPANT->value,
            'colour' => CalendarEventColoursEnum::GREEN->value,
        ]);

        CreateMentorSessionForCalendarEvent::run($event);

        expect(MentorSession::query()->count())->toBe(0);
    });

    it('does not create MentorSession when PARTICIPANT user is missing', function (): void {
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

        CreateMentorSessionForCalendarEvent::run($event);

        expect(MentorSession::query()->count())->toBe(0);
    });
});
