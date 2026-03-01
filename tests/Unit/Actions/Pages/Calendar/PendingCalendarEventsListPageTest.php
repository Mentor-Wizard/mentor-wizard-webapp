<?php

declare(strict_types=1);

use App\Actions\Pages\Calendar\PendingCalendarEventsListPage;
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

mutates(PendingCalendarEventsListPage::class);

describe('PendingCalendarEventsListPage (Unit)', function (): void {
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
            'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL,
            'mentor_program_id' => $this->program->getKey(),
            'date'              => Date::tomorrow()->toDateString(),
        ]);
        $this->event->calendarEventUsers()->attach($this->mentor->getKey());

        auth()->login($this->mentor);
    });

    it('does not filter when mentor program parameter is not a MentorProgram instance',
        function (): void {
            // Create events for different programs
            $event1 = CalendarEvent::factory()->create([
                'mentor_program_id' => $this->mentorProgram1->getKey(),
                'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION->value,
                'start_date_time'   => Date::now()->addDay(),
                'end_date_time'     => Date::now()->addDay()->addHour(),
            ]);

            $event2 = CalendarEvent::factory()->create([
                'mentor_program_id' => $this->mentorProgram2->getKey(),
                'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION->value,
                'start_date_time'   => Date::now()->addDays(2),
                'end_date_time'     => Date::now()->addDays(2)->addHour(),
            ]);

            $this->mentor->calendarEvents()->attach([$event1->getKey(), $event2->getKey()]);

            // Pass null (not an instance of MentorProgram)
            $action = new PendingCalendarEventsListPage;
            $response = $action->handle();
            expect($response)->toBeInstanceOf(Response::class);

            $page = $response->toResponse(request())->getOriginalContent()->getData()['page'];
            $props = $page['props'];

            // Should return ALL events when null is passed (no filtering)
            $allEvents = collect($props['calendarEvents'])->flatten(1);
            expect($allEvents)->toHaveCount(3);
        });

    it('actually filters by mentor program id when provided', function (): void {
        // Create events for different programs
        $event1 = CalendarEvent::factory()->create([
            'mentor_program_id' => $this->mentorProgram1->getKey(),
            'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION->value,
            'start_date_time'   => Date::now()->addDay(),
            'end_date_time'     => Date::now()->addDay()->addHour(),
        ]);

        $event2 = CalendarEvent::factory()->create([
            'mentor_program_id' => $this->mentorProgram2->getKey(),
            'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION->value,
            'start_date_time'   => Date::now()->addDays(2),
            'end_date_time'     => Date::now()->addDays(2)->addHour(),
        ]);

        $this->mentor->calendarEvents()->attach([$event1->getKey(), $event2->getKey()]);

        // Request with mentorProgram1
        $action = new PendingCalendarEventsListPage;
        $response = $action->handle($this->mentorProgram1);

        expect($response)->toBeInstanceOf(Response::class);

        $page = $response->toResponse(request())->getOriginalContent()->getData()['page'];
        $props = $page['props'];

        // Should ONLY have Program A events
        expect($props['calendarEvents'])->toHaveKey('Program A')
            ->and($props['calendarEvents'])->not->toHaveKey('Program B');

        // Verify we have exactly 1 event
        $allEvents = collect($props['calendarEvents'])->flatten(1);
        expect($allEvents)->toHaveCount(1);
    });

    it('loads mentor program relationship with with() clause', function (): void {
        $event = CalendarEvent::factory()->create([
            'mentor_program_id' => $this->mentorProgram1->getKey(),
            'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION->value,
            'start_date_time'   => Date::now()->addDay(),
            'end_date_time'     => Date::now()->addDay()->addHour(),
        ]);

        $this->mentor->calendarEvents()->attach($event->getKey());

        $action = new PendingCalendarEventsListPage;
        $response = $action->handle();

        expect($response)->toBeInstanceOf(Response::class);

        $page = $response->toResponse(request())->getOriginalContent()->getData()['page'];
        $props = $page['props'];

        // Get the first event from the grouped collection
        $eventGroup = $props['calendarEvents']['Program A'];
        $firstEvent = $eventGroup[0];

        // Verify mentor program relationship is loaded
        expect($firstEvent['mentor_program_id'])->toBe($this->mentorProgram1->getKey());
    });

    it('eager loads only id and name columns of mentor program', function (): void {
        $event = CalendarEvent::factory()->create([
            'mentor_program_id' => $this->mentorProgram1->getKey(),
            'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION->value,
            'start_date_time'   => Date::now()->addDay(),
            'end_date_time'     => Date::now()->addDay()->addHour(),
        ]);

        $this->mentor->calendarEvents()->attach($event->getKey());

        $action = new PendingCalendarEventsListPage;
        $response = $action->handle();

        expect($response)->toBeInstanceOf(Response::class);

        $page = $response->toResponse(request())->getOriginalContent()->getData()['page'];
        $props = $page['props'];

        $eventGroup = $props['calendarEvents']['Program A'];
        $firstEvent = $eventGroup[0];
        $mentorProgram = MentorProgram::query()->find($firstEvent['mentor_program_id'])->first();
        // Verify only id and name are loaded (not other columns like created_at, etc.)
        expect($mentorProgram)->toHaveKeys(['id', 'name'])
            ->and($mentorProgram->getAttributes())->toHaveKeys(['id', 'name']);
    });

    it('groups by mentor program name correctly when relationship is loaded', function (): void {
        // Create multiple events for same program
        $event1 = CalendarEvent::factory()->create([
            'mentor_program_id' => $this->mentorProgram1->getKey(),
            'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION->value,
            'start_date_time'   => Date::now()->addDay(),
            'end_date_time'     => Date::now()->addDay()->addHour(),
        ]);

        $event2 = CalendarEvent::factory()->create([
            'mentor_program_id' => $this->mentorProgram1->getKey(),
            'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION->value,
            'start_date_time'   => Date::now()->addDays(2),
            'end_date_time'     => Date::now()->addDays(2)->addHour(),
        ]);

        $this->mentor->calendarEvents()->attach([$event1->getKey(), $event2->getKey()]);

        $action = new PendingCalendarEventsListPage;
        $response = $action->handle();

        expect($response)->toBeInstanceOf(Response::class);

        $page = $response->toResponse(request())->getOriginalContent()->getData()['page'];
        $props = $page['props'];

        // Verify events are grouped by the loaded mentor program name
        expect($props['calendarEvents'])->toHaveKey('Program A')
            ->and($props['calendarEvents']['Program A'])->toHaveCount(2);

        // Verify each event in the group has the mentorProgram loaded
        foreach ($props['calendarEvents']['Program A'] as $event) {
            expect($event['mentor_program_id'])->toBe($this->mentorProgram1->getKey());
        }
    });

    it('returns inertia response with grouped pending events', function (): void {
        $response = new PendingCalendarEventsListPage()->handle();
        expect($response)->toBeInstanceOf(Response::class);

        $page = $response->toResponse(request())->getOriginalContent()->getData()['page'];
        expect($page['component'])->toBe('Calendar/ListPendingCalendarEventsPage')
            ->and($page['props'])->toHaveKeys(['locale', 'calendarEvents'])
            ->and($page['props']['calendarEvents'])->toHaveKey('Program X');
    });

    it('filters by mentor program when provided', function (): void {
        $response = new PendingCalendarEventsListPage()->handle($this->program);
        $page = $response->toResponse(request())->getOriginalContent()->getData()['page'];
        expect($page['props']['calendarEvents'])->toHaveKey('Program X');
    });
});
