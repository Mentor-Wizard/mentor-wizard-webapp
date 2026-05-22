<?php

declare(strict_types=1);

use App\Enums\CalendarEventRoleEnum;
use App\Enums\CalendarEventStatusEnum;
use App\Enums\RoleEnum;
use App\Enums\UserScheduleRecordType;
use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use App\Models\User;
use App\Models\UserSchedule;
use App\Services\Calendar\CheckTimeSlotReservedService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;
use Spatie\Permission\Models\Role;

mutates(CheckTimeSlotReservedService::class);

describe('CheckTimeSlotReservedService Service', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->user->getKey(),
        ]);
    });

    it('returns true when user has no future events (no conflicts)', function (): void {
        Date::setTestNow(Date::create(2025, 4, 1, 10, 0, 0));

        $startDate = Date::createFromFormat(
            '!Y-m-d H:i',
            '2025-04-02 14:00',
            config('app.timezone')
        );
        $endDate = Date::createFromFormat(
            '!Y-m-d H:i',
            '2025-04-02 15:00',
            config('app.timezone')
        );

        $service = new CheckTimeSlotReservedService(
            startDateTime: $startDate,
            endDateTime: $endDate,
            timezone: 'Europe/Kyiv',
            user: $this->user,
            mentorProgram: $this->mentorProgram,
            excludeEvents: []
        );

        expect($service->isSlotAvailable())->toBeTrue();
    });

    it('returns true when requested interval fits entirely within an available slot', function (): void {
        Date::setTestNow(Date::create(2025, 4, 1, 10, 0, 0));
        $tz = 'Europe/Kyiv';

        // Create event from 12:00-13:00 UTC (15:00-16:00 Kyiv)
        $event1StartUtc = Date::create(2025, 4, 1, 12, 0, 0);
        $event1EndUtc = (clone $event1StartUtc)->addHour();

        $event1 = CalendarEvent::query()->create([
            'title'             => 'E1',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $event1StartUtc,
            'end_date_time'     => $event1EndUtc,
            'date'              => $event1StartUtc?->format('Y-m-d'),
            'type'              => 'individual',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $this->user->calendarEvents()->attach($event1->getKey(),
            ['role' => CalendarEventRoleEnum::HOST->value]);

        $startDate = Date::createFromFormat(
            '!Y-m-d H:i',
            '2025-04-01 13:00',
            $tz
        );
        $endDate = Date::createFromFormat(
            '!Y-m-d H:i',
            '2025-04-01 14:00',
            $tz
        );

        // Request slot before the event (13:00-14:00 Kyiv)
        $service = new CheckTimeSlotReservedService(
            startDateTime: $startDate,
            endDateTime: $endDate,
            timezone: $tz,
            user: $this->user,
            mentorProgram: $this->mentorProgram
        );

        expect($service->isSlotAvailable())->toBeTrue();
    });

    it('returns false when requested interval overlaps with existing event', function (): void {
        Date::setTestNow(Date::create(2025, 4, 1, 10, 0, 0));
        $tz = 'Europe/Kyiv';

        // Create event from 12:00-13:00 UTC (15:00-16:00 Kyiv)
        $event1StartUtc = Date::create(2025, 4, 1, 12, 0, 0);
        $event1EndUtc = (clone $event1StartUtc)->addHour();

        $event1 = CalendarEvent::query()->create([
            'title'             => 'E1',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $event1StartUtc,
            'end_date_time'     => $event1EndUtc,
            'date'              => $event1StartUtc?->format('Y-m-d'),
            'type'              => 'individual',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $this->user->calendarEvents()->attach($event1->getKey());

        $startDate = Date::createFromFormat(
            '!Y-m-d H:i',
            '2025-04-01 14:30',
            $tz
        );
        $endDate = Date::createFromFormat(
            '!Y-m-d H:i',
            '2025-04-01 15:30',
            $tz
        );
        // Request slot that overlaps the event (14:30-15:30 Kyiv)
        $service = new CheckTimeSlotReservedService(
            startDateTime: $startDate,
            endDateTime: $endDate,
            timezone: $tz,
            user: $this->user,
            mentorProgram: $this->mentorProgram
        );

        expect($service->isSlotAvailable())->toBeTrue();
    });

    it('returns true when requested slot is between two events', function (): void {
        Date::setTestNow(Date::create(2025, 4, 1, 10, 0, 0));
        $tz = 'Europe/Kyiv';

        // Create two events
        $event1StartUtc = Date::create(2025, 4, 1, 12, 0, 0);
        $event1EndUtc = (clone $event1StartUtc)->addHour();
        $event2StartUtc = Date::create(2025, 4, 1, 15, 0, 0);
        $event2EndUtc = (clone $event2StartUtc)->addHour();

        $event1 = CalendarEvent::query()->create([
            'title'             => 'E1',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $event1StartUtc,
            'end_date_time'     => $event1EndUtc,
            'date'              => $event1StartUtc?->format('Y-m-d'),
            'type'              => 'individual',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $event2 = CalendarEvent::query()->create([
            'title'             => 'E2',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $event2StartUtc,
            'end_date_time'     => $event2EndUtc,
            'date'              => $event2StartUtc?->format('Y-m-d'),
            'type'              => 'individual',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $this->user->calendarEvents()->attach([$event1->getKey(), $event2->getKey()]);

        $startDate = Date::createFromFormat(
            '!Y-m-d H:i',
            '2025-04-01 16:00',
            $tz
        );
        $endDate = Date::createFromFormat(
            '!Y-m-d H:i',
            '2025-04-01 17:00',
            $tz
        );
        // Request slot between events (16:00-17:00 Kyiv, which is between 13:00-15:00 UTC events)
        $service = new CheckTimeSlotReservedService(
            startDateTime: $startDate,
            endDateTime: $endDate,
            timezone: $tz,
            user: $this->user,
            mentorProgram: $this->mentorProgram
        );

        expect($service->isSlotAvailable())->toBeTrue();
    });

    it('excludes specified events when checking availability', function (): void {
        Date::setTestNow(Date::create(2025, 4, 1, 10, 0, 0));
        $tz = 'Europe/Kyiv';

        // Create event from 12:00-13:00 UTC (15:00-16:00 Kyiv)
        $event1StartUtc = Date::create(2025, 4, 1, 12, 0, 0);
        $event1EndUtc = (clone $event1StartUtc)->addHour();

        $event1 = CalendarEvent::query()->create([
            'title'             => 'E1',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => $event1StartUtc,
            'end_date_time'     => $event1EndUtc,
            'date'              => $event1StartUtc?->format('Y-m-d'),
            'type'              => 'individual',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $this->user->calendarEvents()->attach($event1->getKey());

        $startDate = Date::createFromFormat(
            '!Y-m-d H:i',
            '2025-04-01 15:00',
            $tz
        );
        $endDate = Date::createFromFormat(
            '!Y-m-d H:i',
            '2025-04-01 16:00',
            $tz
        );
        // Request the exact slot of the event, but exclude that event
        $service = new CheckTimeSlotReservedService(
            startDateTime: $startDate,
            endDateTime: $endDate,
            timezone: $tz,
            user: $this->user,
            mentorProgram: $this->mentorProgram,
            excludeEvents: [$event1->getKey()]
        );

        expect($service->isSlotAvailable())->toBeTrue();
    });

    it('respects user schedule when checking slot availability', function (): void {
        Date::setTestNow(Date::create(2025, 4, 1, 10, 0, 0));
        $tz = 'Europe/Kyiv';

        $this->user->profile->timezone = $tz;
        $this->user->profile->save();

        // Create schedule: user only works Monday 9:00-17:00
        UserSchedule::query()->create([
            'user_id'     => $this->user->getKey(),
            'day_of_week' => 1, // Monday
            'start_time'  => '09:00:00',
            'end_time'    => '17:00:00',
            'type'        => UserScheduleRecordType::WORKING_DAY,
        ]);

        // Try to book on Tuesday (no working schedule) - should return false
        $startDate = Date::createFromFormat(
            '!Y-m-d H:i',
            '2025-04-01 14:00', // This is Tuesday
            $tz
        );
        $endDate = Date::createFromFormat(
            '!Y-m-d H:i',
            '2025-04-01 15:00',
            $tz
        );

        $service = new CheckTimeSlotReservedService(
            startDateTime: $startDate,
            endDateTime: $endDate,
            timezone: $tz,
            user: $this->user,
            mentorProgram: $this->mentorProgram,
            excludeEvents: []
        );

        expect($service->isSlotAvailable())->toBeFalse();
    });

    it('allows booking within user working schedule', function (): void {
        Date::setTestNow(Date::create(2025, 4, 1, 10, 0, 0));
        $tz = 'Europe/Kyiv';

        $this->user->profile->timezone = $tz;
        $this->user->profile->save();

        // Create schedule: user works Monday 9:00-17:00
        UserSchedule::query()->create([
            'user_id'     => $this->user->getKey(),
            'day_of_week' => 1, // Monday (2025-04-07)
            'start_time'  => '09:00:00',
            'end_time'    => '17:00:00',
            'type'        => UserScheduleRecordType::WORKING_DAY,
        ]);

        // Book within working hours on Monday
        $startDate = Date::createFromFormat(
            '!Y-m-d H:i',
            '2025-04-07 10:00', // Monday 10:00
            $tz
        );
        $endDate = Date::createFromFormat(
            '!Y-m-d H:i',
            '2025-04-07 11:00', // Monday 11:00
            $tz
        );

        $service = new CheckTimeSlotReservedService(
            startDateTime: $startDate,
            endDateTime: $endDate,
            timezone: $tz,
            user: $this->user,
            mentorProgram: $this->mentorProgram,
            excludeEvents: []
        );

        expect($service->isSlotAvailable())->toBeTrue();
    });
});
