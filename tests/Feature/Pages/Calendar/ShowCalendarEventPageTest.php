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
use Illuminate\Support\Facades\Date;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

describe('Calendar Pages - ShowCalendarEvent', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        $this->host = User::factory()->create();
        $this->host->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->host->getKey(),
        ]);
        $this->participant = User::factory()->create();
        $this->stranger = User::factory()->create();

        $this->event = CalendarEvent::factory()->create([
            'title'             => 'Event to Show',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => Date::tomorrow()->format('Y-m-d').' 12:00:00',
            'end_date_time'     => Date::tomorrow()->format('Y-m-d').' 13:00:00',
            'date'              => Date::tomorrow()->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'description'       => 'Details',
            'mentor_program_id' => $mentorProgram->getKey(),
        ]);

        // Attach relations
        $this->event->calendarEventUsers()->attach($this->host->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);
        $this->event->calendarEventUsers()->attach($this->participant->getKey(), [
            'role'   => CalendarEventRoleEnum::PARTICIPANT->value,
            'colour' => CalendarEventColoursEnum::GREEN->value,
        ]);
    });

    it('redirects guests to login', function (): void {
        $this->get(route('pages.calendar.show', $this->event->getKey()))
            ->assertRedirect(route('login'));
    });

    it('allows host to view with edit permissions', function (): void {
        $response = $this->actingAs($this->host)
            ->get(route('pages.calendar.show', $this->event->getKey()));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Calendar/ShowEditCalendarEvent')
            ->has('availableColours')
            ->where('permissions', 'edit')
            ->has('calendarEvent')
        );
    });

    it('allows participant to view with view permissions', function (): void {
        $response = $this->actingAs($this->participant)
            ->get(route('pages.calendar.show', $this->event->getKey()));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Calendar/ShowEditCalendarEvent')
            ->where('permissions', 'view')
            ->has('calendarEvent')
        );
    });

    it('forbids unrelated user by policy', function (): void {
        $this->actingAs($this->stranger)
            ->get(route('pages.calendar.show', $this->event->getKey()))
            ->assertForbidden();
    });
});
