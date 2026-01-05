<?php

declare(strict_types=1);

use App\Enums\CalendarEventRoleEnum;
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
        $this->stranger = User::factory()->create();

        // Program owned by mentor
        $this->program = MentorProgram::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
        ]);

        // Event under the mentor program
        $this->event = CalendarEvent::factory()->create([
            'date'              => Date::tomorrow()->toDateString(),
            'mentor_program_id' => $this->program->getKey(),
            'web_link'          => null,
        ]);

        // Attach mentee to event
        $this->event->calendarEventUsers()->attach($this->mentee->getKey(), [
            'role' => CalendarEventRoleEnum::MENTI,
        ]);
    });

    it('mentor can update web link', function (): void {
        actingAs($this->mentor);
        Auth::login($this->mentor);

        $response = $this->patch(route('pages.calendar.edit', $this->event), [
            'webLink' => 'https://meet.example.com/room-1',
        ]);

        $response->assertRedirect(route('pages.calendar.index'));
        $this->assertDatabaseHas('calendar_events', [
            'id' => $this->event->getKey(),
            'web_link' => 'https://meet.example.com/room-1',
        ]);
    });

    it('mentee cannot update web link, gets 403', function (): void {
        actingAs($this->mentee);
        Auth::login($this->mentee);

        $response = $this->patch(route('pages.calendar.edit', $this->event), [
            'webLink' => 'https://blocked.example',
        ]);

        $response->assertForbidden();
    });

    it('stranger cannot update web link, gets 403', function (): void {
        actingAs($this->stranger);
        Auth::login($this->stranger);

        $response = $this->patch(route('pages.calendar.edit', $this->event), [
            'webLink' => 'https://blocked.example',
        ]);

        $response->assertForbidden();
    });

    it('mentee can delete the event', function (): void {
        actingAs($this->mentee);
        Auth::login($this->mentee);

        $response = $this->delete(route('pages.calendar.delete', $this->event));

        $response->assertRedirect(route('pages.calendar.index'));
        $this->assertDatabaseMissing('calendar_events', [
            'id' => $this->event->getKey(),
        ]);
    });
});
