<?php

declare(strict_types=1);

use App\Enums\UserScheduleRecordType;
use App\Models\User;
use App\Models\UserSchedule;
use App\Services\Calendar\AvailableSlotOptionsForMentorProgram;
use Database\Seeders\RoleSeeder;
use Modules\Calendar\Models\CalendarEvent;
use Modules\Calendar\Services\SplitSlotsPerSessionDuration;
use Modules\MentorProgram\Models\MentorProgram;

describe('AvailableSlotOptionsForMentorProgram (Unit)', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        $this->mentor = User::factory()->create();
        $this->mentor->profile->timezone = 'UTC';
        $this->mentor->profile->save();

    });
    it('builds from calendar event and returns SplitSlotsPerSessionDuration instance', function (): void {

        $program = MentorProgram::factory()->create([
            'mentor_id'        => $this->mentor->getKey(),
            'session_duration' => 30,
        ]);

        $event = CalendarEvent::factory()->create([
            'mentor_program_id' => $program->getKey(),
        ]);

        $service = new AvailableSlotOptionsForMentorProgram($event);
        $result = $service->getAvailableSlots();

        expect($result)->toBeInstanceOf(SplitSlotsPerSessionDuration::class);

        $split = $result->getSplitSlots();
        expect($split)->toBeArray();
    });

    it('respects mentor schedule when getting available slots', function (): void {
        $tz = 'Europe/Kyiv';

        $this->mentor->profile->timezone = $tz;
        $this->mentor->profile->save();

        $mentorProgram = MentorProgram::factory()->create([
            'mentor_id'        => $this->mentor->getKey(),
            'session_duration' => 60,
        ]);

        // Create schedule: mentor only works Monday 9:00-17:00
        UserSchedule::query()->create([
            'user_id'     => $this->mentor->getKey(),
            'day_of_week' => 1, // Monday
            'start_time'  => '09:00:00',
            'end_time'    => '17:00:00',
            'type'        => UserScheduleRecordType::WORKING_DAY,
        ]);

        $calendarEvent = CalendarEvent::factory()->create([
            'mentor_program_id' => $mentorProgram->getKey(),
        ]);

        $service = new AvailableSlotOptionsForMentorProgram($calendarEvent);
        $result = $service->getAvailableSlots();

        $slots = $result->getSplitSlots();

        // Verify slots only exist within Monday 9:00-17:00 working hours
        foreach ($slots as $daySlots) {
            foreach ($daySlots as $slot) {
                $dayOfWeek = $slot['start']->dayOfWeek;
                $hour = $slot['start']->hour;

                // All slots should be on Monday (1) and between 9-17
                expect($dayOfWeek)->toBe(1);
                expect($hour)->toBeGreaterThanOrEqual(9)
                    ->toBeLessThan(17);
            }
        }
    });
});
