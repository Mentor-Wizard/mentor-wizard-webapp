<?php

declare(strict_types=1);

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

describe('PendingCalendarEventsListPage (Feature)', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->mentor = User::factory()->create();
        $this->mentor->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $this->mentee = User::factory()->create();

        $this->programA = MentorProgram::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
            'name'      => 'Program A',
        ]);
        $this->programB = MentorProgram::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
            'name'      => 'Program B',
        ]);

        // Two pending events in different programs
        $this->eventA = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL,
            'mentor_program_id' => $this->programA->getKey(),
            'date'              => Date::tomorrow()->toDateString(),
        ]);
        $this->eventB = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL,
            'mentor_program_id' => $this->programB->getKey(),
            'date'              => Date::tomorrow()->toDateString(),
        ]);

        // Attach mentor/user to events so that they are returned for mentor
        $this->eventA->calendarEventUsers()->attach($this->mentor->getKey());
    });

    it('redirects guest to login', function (): void {
        $this->get(route('pages.calendar.pending'))
            ->assertRedirect(route('login'));
    });

    it('renders grouped pending events by mentor program name', function (): void {
        $this->actingAs($this->mentor);

        // Only eventA has mentor attached; attach eventB as well to be visible
        $this->eventB->calendarEventUsers()->attach($this->mentor->getKey());

        $response = $this->get(route('pages.calendar.pending'));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Calendar/ListPendingCalendarEventsPage')
            ->has('locale')
            ->has('calendarEvents', fn (AssertableJson $events): AssertableJson => $events
                ->whereType('Program A', 'array')
                ->whereType('Program B', 'array')
                ->etc()
            )
        );
    });

    it('filters by mentor program slug when provided', function (): void {
        $this->actingAs($this->mentor);
        $this->eventB->calendarEventUsers()->attach($this->mentor->getKey());

        $response = $this->get(route('pages.calendar.pending', $this->programA->slug));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Calendar/ListPendingCalendarEventsPage')
            ->has('calendarEvents', fn (AssertableJson $events): AssertableJson => $events
                ->has('Program A')
            )
        );
    });
});
