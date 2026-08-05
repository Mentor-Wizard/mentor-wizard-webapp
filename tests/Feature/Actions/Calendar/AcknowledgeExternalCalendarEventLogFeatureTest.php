<?php

declare(strict_types=1);

use App\Enums\ExternalCalendarEventLogTypeEnum;
use App\Enums\RoleEnum;
use App\Models\ExternalCalendarEventLog;
use App\Models\MentorProgram;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Modules\Calendar\Models\CalendarEvent;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

describe('AcknowledgeExternalCalendarEventLog Feature', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        config(['calendar.encryption_key1' => base64_encode(random_bytes(32))]);

        $this->mentor = User::factory()->create();
        $this->mentor->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->mentor->id,
        ]);
    });

    it('allows the mentor of the program to acknowledge the log', function (): void {
        $log = ExternalCalendarEventLog::factory()->create([
            'type'              => ExternalCalendarEventLogTypeEnum::Error,
            'calendar_event_id' => CalendarEvent::factory()->create([
                'mentor_program_id' => $this->mentorProgram->id,
            ]),
        ]);

        actingAs($this->mentor)
            ->patch(route('external-calendar.log.acknowledge', ['log' => $log->id]))
            ->assertRedirect()
            ->assertSessionHas('success', 'Error acknowledged.');

        expect($log->fresh()->type)->toBe(ExternalCalendarEventLogTypeEnum::ErrorStatusViewed);
    });

    it('forbids other users from acknowledging the log', function (): void {
        $otherUser = User::factory()->create();
        $log = ExternalCalendarEventLog::factory()->create([
            'type'              => ExternalCalendarEventLogTypeEnum::Error,
            'calendar_event_id' => CalendarEvent::factory()->create([
                'mentor_program_id' => $this->mentorProgram->id,
            ]),
        ]);

        actingAs($otherUser)
            ->patch(route('external-calendar.log.acknowledge', ['log' => $log->id]))
            ->assertForbidden();

        expect($log->fresh()->type)->toBe(ExternalCalendarEventLogTypeEnum::Error);
    });

    it('returns error when acknowledging non-error logs', function (): void {
        $log = ExternalCalendarEventLog::factory()->create([
            'type'              => ExternalCalendarEventLogTypeEnum::Success,
            'calendar_event_id' => CalendarEvent::factory()->create([
                'mentor_program_id' => $this->mentorProgram->id,
            ]),
        ]);

        actingAs($this->mentor)
            ->patch(route('external-calendar.log.acknowledge', ['log' => $log->id]))
            ->assertRedirect()
            ->assertSessionHas('error', 'Only error logs can be acknowledged.');

        expect($log->fresh()->type)->toBe(ExternalCalendarEventLogTypeEnum::Success);
    });
});
