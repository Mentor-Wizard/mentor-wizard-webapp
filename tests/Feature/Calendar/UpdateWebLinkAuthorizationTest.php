<?php

declare(strict_types=1);

use App\Enums\CalendarEventColoursEnum;
use App\Enums\CalendarEventRoleEnum;
use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Enums\RoleEnum;
use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

describe('Calendar event web link update authorization (mentor vs mentee)', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        // Mentor and mentee users
        $this->mentor = User::factory()->create();
        $this->mentor->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $this->mentee = User::factory()->create();

        // Another random user
        $this->visitor = User::factory()->create();

        // Program owned by mentor
        $this->program = MentorProgram::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
        ]);

        // Event under the mentor program
        $this->event = CalendarEvent::factory()->create([
            'date'              => Date::tomorrow()->toDateString(),
            'web_link'          => null,
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'mentor_program_id' => $this->program->getKey(),
        ]);

        // Attach mentee to event
        $this->event->calendarEventUsers()->attach($this->mentee->getKey(), [
            'role' => CalendarEventRoleEnum::MENTI,
        ]);
    });

    it('mentor can update web link', function (): void {
        actingAs($this->mentor);
        Auth::login($this->mentor);

        $response = $this->withSession(['_token' => 'test_token'])
            ->patch(route('pages.calendar.edit', $this->event), [
                'title'             => 'New title',
                'fromTime'          => '10:00',
                'toTime'            => '11:00',
                'fromDate'          => Date::tomorrow()->format('Y-m-d'),
                'toDate'            => Date::tomorrow()->format('Y-m-d'),
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'colour'            => CalendarEventColoursEnum::BLUE->value,
                'webLink'           => 'https://meet.example.com/room-1',
                'mentor_program_id' => $this->program->getKey(),
                '_token'            => 'test_token',
            ]);

        $response->assertRedirect(route('pages.calendar.index'));
        $this->assertDatabaseHas('calendar_events', [
            'id'       => $this->event->getKey(),
            'web_link' => 'https://meet.example.com/room-1',
        ]);
    });

    it('mentee cannot update web link, gets 403', function (): void {
        actingAs($this->mentee);
        Auth::login($this->mentee);

        $response = $this
            ->withSession(['_token' => 'test_token'])
            ->patch(route('pages.calendar.edit', $this->event), [
                'webLink' => 'https://blocked.example',
                '_token'  => 'test_token',
            ]);

        $response->assertForbidden();
    });

    it('not related visitor cannot update web link, gets 403', function (): void {
        actingAs($this->visitor);
        Auth::login($this->visitor);

        $response = $this
            ->withSession(['_token' => 'test_token'])
            ->patch(route('pages.calendar.edit', [
                $this->event,
                '_token' => 'test_token',
            ]), [
                'webLink' => 'https://blocked.example',
            ]);

        $response->assertForbidden();
    });
});
