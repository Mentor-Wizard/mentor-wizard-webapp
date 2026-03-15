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

describe('ConfirmedCalendarEventsListPage (Feature)', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->mentor = User::factory()->create();
        $this->mentor->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $this->programA = MentorProgram::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
            'name'      => 'Program A',
        ]);
        $this->programB = MentorProgram::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
            'name'      => 'Program B',
        ]);
    });

    it('redirects guest to login', function (): void {
        $this->get(route('pages.calendar.confirmed'))
            ->assertRedirect(route('login'));
    });

    it('renders confirmed upcoming events grouped by mentor program name', function (): void {
        $this->actingAs($this->mentor);

        $eventA = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL,
            'mentor_program_id' => $this->programA->getKey(),
            'date'              => Date::tomorrow()->toDateString(),
            'start_date_time'   => Date::now()->addDay(),
            'end_date_time'     => Date::now()->addDay()->addHour(),
        ]);
        $eventA->calendarEventUsers()->attach($this->mentor->getKey());

        $eventB = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL,
            'mentor_program_id' => $this->programB->getKey(),
            'date'              => Date::tomorrow()->toDateString(),
            'start_date_time'   => Date::now()->addDays(2),
            'end_date_time'     => Date::now()->addDays(2)->addHour(),
        ]);
        $eventB->calendarEventUsers()->attach($this->mentor->getKey());

        $response = $this->get(route('pages.calendar.confirmed'));

        $response->assertStatus(Response::HTTP_OK);

        $response->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Calendar/ListConfirmedCalendarEventsPage')
            ->has('locale')
            ->has('calendarEvents', fn (AssertableJson $events): AssertableJson => $events
                ->whereType((string) $this->programA->getKey(), 'array')
                ->whereType((string) $this->programB->getKey(), 'array')
                ->etc()
            )
        );
    });

    it('excludes past confirmed events', function (): void {
        $this->actingAs($this->mentor);

        $pastEvent = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL,
            'mentor_program_id' => $this->programA->getKey(),
            'date'              => Date::yesterday()->toDateString(),
            'start_date_time'   => Date::now()->subDay(),
            'end_date_time'     => Date::now()->subDay()->addHour(),
        ]);
        $pastEvent->calendarEventUsers()->attach($this->mentor->getKey());

        $response = $this->get(route('pages.calendar.confirmed'));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Calendar/ListConfirmedCalendarEventsPage')
            ->where('calendarEvents', [])
        );
    });

    it('excludes pending events', function (): void {
        $this->actingAs($this->mentor);

        $pendingEvent = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL,
            'mentor_program_id' => $this->programA->getKey(),
            'date'              => Date::tomorrow()->toDateString(),
            'start_date_time'   => Date::now()->addDay(),
            'end_date_time'     => Date::now()->addDay()->addHour(),
        ]);
        $pendingEvent->calendarEventUsers()->attach($this->mentor->getKey());

        $response = $this->get(route('pages.calendar.confirmed'));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Calendar/ListConfirmedCalendarEventsPage')
            ->where('calendarEvents', [])
        );
    });

    it('filters by mentor program slug when provided', function (): void {
        $this->actingAs($this->mentor);

        $eventA = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL,
            'mentor_program_id' => $this->programA->getKey(),
            'date'              => Date::tomorrow()->toDateString(),
            'start_date_time'   => Date::now()->addDay(),
            'end_date_time'     => Date::now()->addDay()->addHour(),
        ]);
        $eventA->calendarEventUsers()->attach($this->mentor->getKey());

        $eventB = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL,
            'mentor_program_id' => $this->programB->getKey(),
            'date'              => Date::tomorrow()->toDateString(),
            'start_date_time'   => Date::now()->addDays(2),
            'end_date_time'     => Date::now()->addDays(2)->addHour(),
        ]);
        $eventB->calendarEventUsers()->attach($this->mentor->getKey());

        $response = $this->get(route('pages.calendar.confirmed', $this->programA->slug));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Calendar/ListConfirmedCalendarEventsPage')
            ->has('calendarEvents', fn (AssertableJson $events): AssertableJson => $events
                ->has((string) $this->programA->getKey())
            )
        );
    });

    it('returns empty calendarEvents when no confirmed future events exist', function (): void {
        $this->actingAs($this->mentor);

        $response = $this->get(route('pages.calendar.confirmed'));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Calendar/ListConfirmedCalendarEventsPage')
            ->has('locale')
            ->where('calendarEvents', [])
        );
    });
});
