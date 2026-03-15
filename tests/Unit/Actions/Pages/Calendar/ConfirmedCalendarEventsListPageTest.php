<?php

declare(strict_types=1);

use App\Actions\Pages\Calendar\ConfirmedCalendarEventsListPage;
use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Enums\RoleEnum;
use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;
use Inertia\Response;
use Spatie\Permission\Models\Role;

mutates(ConfirmedCalendarEventsListPage::class);

describe('ConfirmedCalendarEventsListPage (Unit)', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->mentor = User::factory()->create();
        $this->mentor->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $this->program = MentorProgram::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
            'name'      => 'Program X',
        ]);

        $this->mentorProgram1 = MentorProgram::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
            'name'      => 'Program A',
        ]);

        $this->mentorProgram2 = MentorProgram::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
            'name'      => 'Program B',
        ]);

        $this->event = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL,
            'mentor_program_id' => $this->program->getKey(),
            'date'              => Date::tomorrow()->toDateString(),
            'start_date_time'   => Date::now()->addDay(),
            'end_date_time'     => Date::now()->addDay()->addHour(),
        ]);
        $this->event->calendarEventUsers()->attach($this->mentor->getKey());

        auth()->login($this->mentor);
    });

    it('returns inertia response with correct component and props', function (): void {
        $response = new ConfirmedCalendarEventsListPage()->handle();
        expect($response)->toBeInstanceOf(Response::class);

        $page = $response->toResponse(request())->getOriginalContent()->getData()['page'];
        expect($page['component'])->toBe('Calendar/ListConfirmedCalendarEventsPage')
            ->and($page['props'])->toHaveKeys(['locale', 'calendarEvents'])
            ->and($page['props']['calendarEvents'])->toHaveKey('1')
            ->and($page['props']['calendarEvents']['1']['name'])->toBe('Program X');
    });

    it('only includes confirmed events in the future', function (): void {
        // Create a confirmed past event
        $pastEvent = CalendarEvent::factory()->create([
            'mentor_program_id' => $this->mentorProgram1->getKey(),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => Date::now()->subDay(),
            'end_date_time'     => Date::now()->subDay()->addHour(),
        ]);
        $pastEvent->calendarEventUsers()->attach($this->mentor->getKey());

        // Create a pending future event
        $pendingEvent = CalendarEvent::factory()->create([
            'mentor_program_id' => $this->mentorProgram1->getKey(),
            'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION->value,
            'start_date_time'   => Date::now()->addDays(3),
            'end_date_time'     => Date::now()->addDays(3)->addHour(),
        ]);
        $pendingEvent->calendarEventUsers()->attach($this->mentor->getKey());

        // Create a confirmed future event
        $futureEvent = CalendarEvent::factory()->create([
            'mentor_program_id' => $this->mentorProgram1->getKey(),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => Date::now()->addDays(2),
            'end_date_time'     => Date::now()->addDays(2)->addHour(),
        ]);
        $futureEvent->calendarEventUsers()->attach($this->mentor->getKey());

        $action = new ConfirmedCalendarEventsListPage;
        $response = $action->handle();

        expect($response)->toBeInstanceOf(Response::class);

        $page = $response->toResponse(request())->getOriginalContent()->getData()['page'];
        $props = $page['props'];

        // Should have Program X (from beforeEach) and Program A (future confirmed)
        $allEvents = collect($props['calendarEvents'][$this->mentorProgram1->getKey()]['events']);

        // The past and pending events should be excluded
        $eventIds = $allEvents->pluck('id')->toArray();
        expect($eventIds)->not->toContain($pastEvent->getKey())
            ->and($eventIds)->not->toContain($pendingEvent->getKey())
            ->and($eventIds)->toContain($futureEvent->getKey());
    });

    it('groups events by mentor program name', function (): void {
        $event1 = CalendarEvent::factory()->create([
            'mentor_program_id' => $this->mentorProgram1->getKey(),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => Date::now()->addDay(),
            'end_date_time'     => Date::now()->addDay()->addHour(),
        ]);

        $event2 = CalendarEvent::factory()->create([
            'mentor_program_id' => $this->mentorProgram1->getKey(),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => Date::now()->addDays(2),
            'end_date_time'     => Date::now()->addDays(2)->addHour(),
        ]);

        $this->mentor->calendarEvents()->attach([$event1->getKey(), $event2->getKey()]);

        $action = new ConfirmedCalendarEventsListPage;
        $response = $action->handle();

        expect($response)->toBeInstanceOf(Response::class);

        $page = $response->toResponse(request())->getOriginalContent()->getData()['page'];
        $props = $page['props'];

        expect($props['calendarEvents'][$this->mentorProgram1->getKey()]['name'])->toBe('Program A')
            ->and($props['calendarEvents'][$this->mentorProgram1->getKey()]['events'])->toHaveCount(2);

        foreach ($props['calendarEvents'][$this->mentorProgram1->getKey()]['events'] as $event) {
            expect($event['mentor_program_id'])->toBe($this->mentorProgram1->getKey());
        }
    });

    it('filters by mentor program when provided', function (): void {
        $event1 = CalendarEvent::factory()->create([
            'mentor_program_id' => $this->mentorProgram1->getKey(),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => Date::now()->addDay(),
            'end_date_time'     => Date::now()->addDay()->addHour(),
        ]);

        $event2 = CalendarEvent::factory()->create([
            'mentor_program_id' => $this->mentorProgram2->getKey(),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => Date::now()->addDays(2),
            'end_date_time'     => Date::now()->addDays(2)->addHour(),
        ]);

        $this->mentor->calendarEvents()->attach([$event1->getKey(), $event2->getKey()]);

        $action = new ConfirmedCalendarEventsListPage;
        $response = $action->handle($this->mentorProgram1);

        expect($response)->toBeInstanceOf(Response::class);

        $page = $response->toResponse(request())->getOriginalContent()->getData()['page'];
        $props = $page['props'];

        expect($props['calendarEvents'][$this->mentorProgram1->getKey()]['name'])->toBe('Program A')
            ->and($props['calendarEvents'])->not->toHaveKey($this->mentorProgram2->getKey());

        $allEvents = collect($props['calendarEvents'][$this->mentorProgram1->getKey()]['events']);
        expect($allEvents)->toHaveCount(1);
    });

    it('does not filter when no mentor program provided', function (): void {
        $event1 = CalendarEvent::factory()->create([
            'mentor_program_id' => $this->mentorProgram1->getKey(),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => Date::now()->addDay(),
            'end_date_time'     => Date::now()->addDay()->addHour(),
        ]);

        $event2 = CalendarEvent::factory()->create([
            'mentor_program_id' => $this->mentorProgram2->getKey(),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => Date::now()->addDays(2),
            'end_date_time'     => Date::now()->addDays(2)->addHour(),
        ]);

        $this->mentor->calendarEvents()->attach([$event1->getKey(), $event2->getKey()]);

        $action = new ConfirmedCalendarEventsListPage;
        $response = $action->handle();

        expect($response)->toBeInstanceOf(Response::class);

        $page = $response->toResponse(request())->getOriginalContent()->getData()['page'];
        $props = $page['props'];

        // Should return ALL events when null is passed (no filtering)
        $allEvents = collect($props['calendarEvents']);
        expect($allEvents)->toHaveCount(3); // 2 new + 1 from beforeEach
    });

    it('orders events by start_date_time ascending', function (): void {
        $laterEvent = CalendarEvent::factory()->create([
            'mentor_program_id' => $this->mentorProgram1->getKey(),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => Date::now()->addDays(5),
            'end_date_time'     => Date::now()->addDays(5)->addHour(),
        ]);

        $earlierEvent = CalendarEvent::factory()->create([
            'mentor_program_id' => $this->mentorProgram1->getKey(),
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => Date::now()->addDays(2),
            'end_date_time'     => Date::now()->addDays(2)->addHour(),
        ]);

        $this->mentor->calendarEvents()->attach([$laterEvent->getKey(), $earlierEvent->getKey()]);

        $action = new ConfirmedCalendarEventsListPage;
        $response = $action->handle();

        expect($response)->toBeInstanceOf(Response::class);

        $page = $response->toResponse(request())->getOriginalContent()->getData()['page'];
        $props = $page['props'];

        $programAEvents = $props['calendarEvents'][$this->mentorProgram1->getKey()]['events'];

        // Earlier event should come before later event
        expect($programAEvents[0]['id'])->toBe($earlierEvent->getKey())
            ->and($programAEvents[1]['id'])->toBe($laterEvent->getKey());
    });
});
