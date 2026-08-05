<?php

declare(strict_types=1);

use Modules\Calendar\Enums\CalendarEventColoursEnum;
use Modules\Calendar\Enums\CalendarEventRoleEnum;
use Modules\Calendar\Enums\CalendarEventStatusEnum;
use Modules\Calendar\Enums\CalendarEventTypeEnum;
use App\Enums\RoleEnum;
use Modules\Calendar\Models\CalendarEvent;
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
    });

    it('redirects guest to login', function (): void {
        $this->get(route('pages.calendar.pending'))
            ->assertRedirect(route('login'));
    });

    it('returns upcomingCalendarEvents and pastCalendarEvents props', function (): void {
        $this->actingAs($this->mentor);

        $upcoming = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL,
            'mentor_program_id' => $this->programA->getKey(),
            'start_date_time'   => Date::tomorrow()->startOfDay(),
            'end_date_time'     => Date::tomorrow()->startOfDay()->addHour(),
            'date'              => Date::tomorrow()->toDateString(),
        ]);
        $upcoming->calendarEventUsers()->attach($this->mentor->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        $past = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL,
            'mentor_program_id' => $this->programA->getKey(),
            'start_date_time'   => Date::yesterday()->startOfDay(),
            'end_date_time'     => Date::yesterday()->startOfDay()->addHour(),
            'date'              => Date::yesterday()->toDateString(),
        ]);
        $past->calendarEventUsers()->attach($this->mentor->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        $this->get(route('pages.calendar.pending'))
            ->assertStatus(Response::HTTP_OK)
            ->assertInertia(fn (Assert $page): AssertableJson => $page
                ->component('Calendar/ListPendingCalendarEventsPage')
                ->has('locale')
                ->has('upcomingCalendarEvents')
                ->has('pastCalendarEvents')
            );
    });

    it('groups upcomingCalendarEvents by mentor program name', function (): void {
        $this->actingAs($this->mentor);

        $eventA = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL,
            'mentor_program_id' => $this->programA->getKey(),
            'start_date_time'   => Date::tomorrow()->startOfDay(),
            'end_date_time'     => Date::tomorrow()->startOfDay()->addHour(),
            'date'              => Date::tomorrow()->toDateString(),
        ]);
        $eventA->calendarEventUsers()->attach($this->mentor->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        $eventB = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL,
            'mentor_program_id' => $this->programB->getKey(),
            'start_date_time'   => Date::tomorrow()->startOfDay()->addHours(2),
            'end_date_time'     => Date::tomorrow()->startOfDay()->addHours(3),
            'date'              => Date::tomorrow()->toDateString(),
        ]);
        $eventB->calendarEventUsers()->attach($this->mentor->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        $this->get(route('pages.calendar.pending'))
            ->assertStatus(Response::HTTP_OK)
            ->assertInertia(fn (Assert $page): AssertableJson => $page
                ->component('Calendar/ListPendingCalendarEventsPage')
                ->has('upcomingCalendarEvents', fn (AssertableJson $events): AssertableJson => $events
                    ->whereType('Program A', 'array')
                    ->whereType('Program B', 'array')
                    ->etc()
                )
            );
    });

    it('groups pastCalendarEvents by mentor program name', function (): void {
        $this->actingAs($this->mentor);

        $pastA = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL,
            'mentor_program_id' => $this->programA->getKey(),
            'start_date_time'   => Date::yesterday()->startOfDay(),
            'end_date_time'     => Date::yesterday()->startOfDay()->addHour(),
            'date'              => Date::yesterday()->toDateString(),
        ]);
        $pastA->calendarEventUsers()->attach($this->mentor->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        $pastB = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL,
            'mentor_program_id' => $this->programB->getKey(),
            'start_date_time'   => Date::yesterday()->startOfDay()->subHour(),
            'end_date_time'     => Date::yesterday()->startOfDay(),
            'date'              => Date::yesterday()->toDateString(),
        ]);
        $pastB->calendarEventUsers()->attach($this->mentor->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        $this->get(route('pages.calendar.pending'))
            ->assertStatus(Response::HTTP_OK)
            ->assertInertia(fn (Assert $page): AssertableJson => $page
                ->component('Calendar/ListPendingCalendarEventsPage')
                ->has('pastCalendarEvents', fn (AssertableJson $events): AssertableJson => $events
                    ->whereType('Program A', 'array')
                    ->whereType('Program B', 'array')
                    ->etc()
                )
            );
    });

    it('excludes non-pending events from upcomingCalendarEvents', function (): void {
        $this->actingAs($this->mentor);

        $confirmed = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL,
            'mentor_program_id' => $this->programA->getKey(),
            'start_date_time'   => Date::tomorrow()->startOfDay(),
            'end_date_time'     => Date::tomorrow()->startOfDay()->addHour(),
            'date'              => Date::tomorrow()->toDateString(),
        ]);
        $confirmed->calendarEventUsers()->attach($this->mentor->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        $this->get(route('pages.calendar.pending'))
            ->assertStatus(Response::HTTP_OK)
            ->assertInertia(fn (Assert $page): AssertableJson => $page
                ->component('Calendar/ListPendingCalendarEventsPage')
                ->where('upcomingCalendarEvents', [])
            );
    });

    it('excludes non-pending events from pastCalendarEvents', function (): void {
        $this->actingAs($this->mentor);

        $confirmed = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL,
            'mentor_program_id' => $this->programA->getKey(),
            'start_date_time'   => Date::yesterday()->startOfDay(),
            'end_date_time'     => Date::yesterday()->startOfDay()->addHour(),
            'date'              => Date::yesterday()->toDateString(),
        ]);
        $confirmed->calendarEventUsers()->attach($this->mentor->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        $this->get(route('pages.calendar.pending'))
            ->assertStatus(Response::HTTP_OK)
            ->assertInertia(fn (Assert $page): AssertableJson => $page
                ->component('Calendar/ListPendingCalendarEventsPage')
                ->where('pastCalendarEvents', [])
            );
    });

    it('filters upcomingCalendarEvents by mentor program slug when provided', function (): void {
        $this->actingAs($this->mentor);

        $eventA = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL,
            'mentor_program_id' => $this->programA->getKey(),
            'start_date_time'   => Date::tomorrow()->startOfDay(),
            'end_date_time'     => Date::tomorrow()->startOfDay()->addHour(),
            'date'              => Date::tomorrow()->toDateString(),
        ]);
        $eventA->calendarEventUsers()->attach($this->mentor->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        $eventB = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL,
            'mentor_program_id' => $this->programB->getKey(),
            'start_date_time'   => Date::tomorrow()->startOfDay()->addHours(2),
            'end_date_time'     => Date::tomorrow()->startOfDay()->addHours(3),
            'date'              => Date::tomorrow()->toDateString(),
        ]);
        $eventB->calendarEventUsers()->attach($this->mentor->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        $this->get(route('pages.calendar.pending', $this->programA->slug))
            ->assertStatus(Response::HTTP_OK)
            ->assertInertia(fn (Assert $page): AssertableJson => $page
                ->component('Calendar/ListPendingCalendarEventsPage')
                ->has('upcomingCalendarEvents', fn (AssertableJson $events): AssertableJson => $events
                    ->has('Program A')
                    ->missing('Program B')
                )
            );
    });

    it('filters pastCalendarEvents by mentor program slug when provided', function (): void {
        $this->actingAs($this->mentor);

        $pastA = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL,
            'mentor_program_id' => $this->programA->getKey(),
            'start_date_time'   => Date::yesterday()->startOfDay(),
            'end_date_time'     => Date::yesterday()->startOfDay()->addHour(),
            'date'              => Date::yesterday()->toDateString(),
        ]);
        $pastA->calendarEventUsers()->attach($this->mentor->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        $pastB = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL,
            'mentor_program_id' => $this->programB->getKey(),
            'start_date_time'   => Date::yesterday()->startOfDay()->subHour(),
            'end_date_time'     => Date::yesterday()->startOfDay(),
            'date'              => Date::yesterday()->toDateString(),
        ]);
        $pastB->calendarEventUsers()->attach($this->mentor->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        $this->get(route('pages.calendar.pending', $this->programA->slug))
            ->assertStatus(Response::HTTP_OK)
            ->assertInertia(fn (Assert $page): AssertableJson => $page
                ->component('Calendar/ListPendingCalendarEventsPage')
                ->has('pastCalendarEvents', fn (AssertableJson $events): AssertableJson => $events
                    ->has('Program A')
                    ->missing('Program B')
                )
            );
    });

    it('only shows events belonging to the authenticated mentor', function (): void {
        $this->actingAs($this->mentor);

        $otherMentor = User::factory()->create();
        $otherMentor->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $otherProgram = MentorProgram::factory()->create(['mentor_id' => $otherMentor->getKey()]);

        $otherEvent = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION,
            'mentor_program_id' => $otherProgram->getKey(),
            'start_date_time'   => Date::tomorrow()->startOfDay(),
            'end_date_time'     => Date::tomorrow()->startOfDay()->addHour(),
            'date'              => Date::tomorrow()->toDateString(),
        ]);
        $otherEvent->calendarEventUsers()->attach($otherMentor->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        $this->get(route('pages.calendar.pending'))
            ->assertStatus(Response::HTTP_OK)
            ->assertInertia(fn (Assert $page): AssertableJson => $page
                ->component('Calendar/ListPendingCalendarEventsPage')
                ->where('upcomingCalendarEvents', [])
                ->where('pastCalendarEvents', [])
            );
    });
});
