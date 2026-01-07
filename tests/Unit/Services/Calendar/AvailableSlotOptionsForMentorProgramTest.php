<?php

declare(strict_types=1);

use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use App\Models\User;
use App\Services\Calendar\AvailableSlotOptionsForMentorProgram;
use App\Services\Calendar\SplitSlotsPerSessionDuration;
use Database\Seeders\RoleSeeder;

mutates(AvailableSlotOptionsForMentorProgram::class);

describe('AvailableSlotOptionsForMentorProgram (Unit)', function (): void {
    it('builds from calendar event and returns SplitSlotsPerSessionDuration instance', function (): void {
        $this->seed(RoleSeeder::class);

        $mentor = User::factory()->create();
        $mentor->profile->timezone = 'UTC';
        $mentor->profile->save();

        $program = MentorProgram::factory()->create([
            'mentor_id'        => $mentor->getKey(),
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
});
