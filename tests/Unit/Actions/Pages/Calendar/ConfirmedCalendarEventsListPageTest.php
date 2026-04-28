<?php

declare(strict_types=1);

use App\Actions\Pages\Calendar\ConfirmedCalendarEventsListPage;
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

        $this->upcomingEvent = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL,
            'mentor_program_id' => $this->program->getKey(),
            'date'              => Date::tomorrow()->toDateString(),
            'start_date_time'   => Date::tomorrow()->startOfDay(),
            'end_date_time'     => Date::tomorrow()->startOfDay()->addHour(),
        ]);
        $this->upcomingEvent->calendarEventUsers()->attach($this->mentor->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        auth()->login($this->mentor);
    });

    it('returns inertia response with correct component and props', function (): void {
        $response = new ConfirmedCalendarEventsListPage()->handle(request());
        expect($response)->toBeInstanceOf(Response::class);

        $page = $response->toResponse(request())->getOriginalContent()->getData()['page'];

        expect($page['component'])->toBe('Calendar/ListConfirmedCalendarEventsPage')
            ->and($page['props'])->toHaveKeys(['locale', 'upcomingCalendarEvents', 'pastCalendarEvents']);
    });

    it('places confirmed future events in upcomingCalendarEvents', function (): void {
        $response = new ConfirmedCalendarEventsListPage()->handle(request());
        $page = $response->toResponse(request())->getOriginalContent()->getData()['page'];
        $props = $page['props'];

        $programKey = $this->program->getKey();

        expect($props['upcomingCalendarEvents'])->toHaveKey($programKey)
            ->and($props['upcomingCalendarEvents'][$programKey]['name'])->toBe('Program X')
            ->and($props['upcomingCalendarEvents'][$programKey]['events'])->toHaveCount(1);
    });

    it('places confirmed past events in pastCalendarEvents', function (): void {
        $pastEvent = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL,
            'mentor_program_id' => $this->program->getKey(),
            'date'              => Date::yesterday()->toDateString(),
            'start_date_time'   => Date::yesterday()->startOfDay(),
            'end_date_time'     => Date::yesterday()->startOfDay()->addHour(),
        ]);
        $pastEvent->calendarEventUsers()->attach($this->mentor->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        $response = new ConfirmedCalendarEventsListPage()->handle(request());
        $page = $response->toResponse(request())->getOriginalContent()->getData()['page'];
        $props = $page['props'];

        $programKey = $this->program->getKey();

        expect($props['pastCalendarEvents'])->toHaveKey($programKey)
            ->and($props['pastCalendarEvents'][$programKey]['name'])->toBe('Program X')
            ->and($props['pastCalendarEvents'][$programKey]['events'])->toHaveCount(1);
    });

    it('excludes non-confirmed events from both props', function (): void {
        $pendingEvent = CalendarEvent::factory()->create([
            'mentor_program_id' => $this->mentorProgram1->getKey(),
            'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION,
            'start_date_time'   => Date::tomorrow()->startOfDay(),
            'end_date_time'     => Date::tomorrow()->startOfDay()->addHour(),
        ]);
        $pendingEvent->calendarEventUsers()->attach($this->mentor->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        $response = new ConfirmedCalendarEventsListPage()->handle(request());
        $page = $response->toResponse(request())->getOriginalContent()->getData()['page'];
        $props = $page['props'];

        $upcomingIds = collect($props['upcomingCalendarEvents'])
            ->flatMap(fn (array $group): array => collect($group['events'])->pluck('id')->toArray())
            ->toArray();

        $pastIds = collect($props['pastCalendarEvents'])
            ->flatMap(fn (array $group): array => collect($group['events'])->pluck('id')->toArray())
            ->toArray();

        expect($upcomingIds)->not->toContain($pendingEvent->getKey())
            ->and($pastIds)->not->toContain($pendingEvent->getKey());
    });

    it('groups upcomingCalendarEvents by mentor program id', function (): void {
        $event1 = CalendarEvent::factory()->create([
            'mentor_program_id' => $this->mentorProgram1->getKey(),
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => Date::tomorrow()->startOfDay(),
            'end_date_time'     => Date::tomorrow()->startOfDay()->addHour(),
        ]);
        $event1->calendarEventUsers()->attach($this->mentor->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        $event2 = CalendarEvent::factory()->create([
            'mentor_program_id' => $this->mentorProgram1->getKey(),
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => Date::tomorrow()->startOfDay()->addHours(2),
            'end_date_time'     => Date::tomorrow()->startOfDay()->addHours(3),
        ]);
        $event2->calendarEventUsers()->attach($this->mentor->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        $response = new ConfirmedCalendarEventsListPage()->handle(request());
        $page = $response->toResponse(request())->getOriginalContent()->getData()['page'];
        $props = $page['props'];

        $programKey = $this->mentorProgram1->getKey();

        expect($props['upcomingCalendarEvents'])->toHaveKey($programKey)
            ->and($props['upcomingCalendarEvents'][$programKey]['name'])->toBe('Program A')
            ->and($props['upcomingCalendarEvents'][$programKey]['events'])->toHaveCount(2);

        foreach ($props['upcomingCalendarEvents'][$programKey]['events'] as $event) {
            expect($event['mentor_program_id'])->toBe($programKey);
        }
    });

    it('groups pastCalendarEvents by mentor program id', function (): void {
        $past1 = CalendarEvent::factory()->create([
            'mentor_program_id' => $this->mentorProgram1->getKey(),
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => Date::yesterday()->startOfDay(),
            'end_date_time'     => Date::yesterday()->startOfDay()->addHour(),
        ]);
        $past1->calendarEventUsers()->attach($this->mentor->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        $past2 = CalendarEvent::factory()->create([
            'mentor_program_id' => $this->mentorProgram1->getKey(),
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => Date::yesterday()->startOfDay()->subHour(),
            'end_date_time'     => Date::yesterday()->startOfDay(),
        ]);
        $past2->calendarEventUsers()->attach($this->mentor->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        $response = new ConfirmedCalendarEventsListPage()->handle(request());
        $page = $response->toResponse(request())->getOriginalContent()->getData()['page'];
        $props = $page['props'];

        $programKey = $this->mentorProgram1->getKey();

        expect($props['pastCalendarEvents'])->toHaveKey($programKey)
            ->and($props['pastCalendarEvents'][$programKey]['name'])->toBe('Program A')
            ->and($props['pastCalendarEvents'][$programKey]['events'])->toHaveCount(2);

        foreach ($props['pastCalendarEvents'][$programKey]['events'] as $event) {
            expect($event['mentor_program_id'])->toBe($programKey);
        }
    });

    it('filters upcomingCalendarEvents by mentor program when provided', function (): void {
        $event1 = CalendarEvent::factory()->create([
            'mentor_program_id' => $this->mentorProgram1->getKey(),
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => Date::tomorrow()->startOfDay(),
            'end_date_time'     => Date::tomorrow()->startOfDay()->addHour(),
        ]);
        $event1->calendarEventUsers()->attach($this->mentor->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        $event2 = CalendarEvent::factory()->create([
            'mentor_program_id' => $this->mentorProgram2->getKey(),
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => Date::tomorrow()->startOfDay()->addHours(2),
            'end_date_time'     => Date::tomorrow()->startOfDay()->addHours(3),
        ]);
        $event2->calendarEventUsers()->attach($this->mentor->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        $response = new ConfirmedCalendarEventsListPage()->handle(request(), $this->mentorProgram1);
        $page = $response->toResponse(request())->getOriginalContent()->getData()['page'];
        $props = $page['props'];

        expect($props['upcomingCalendarEvents'])->toHaveKey($this->mentorProgram1->getKey())
            ->and($props['upcomingCalendarEvents'])->not->toHaveKey($this->mentorProgram2->getKey())
            ->and($props['upcomingCalendarEvents'][$this->mentorProgram1->getKey()]['events'])->toHaveCount(1);
    });

    it('filters pastCalendarEvents by mentor program when provided', function (): void {
        $past1 = CalendarEvent::factory()->create([
            'mentor_program_id' => $this->mentorProgram1->getKey(),
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => Date::yesterday()->startOfDay(),
            'end_date_time'     => Date::yesterday()->startOfDay()->addHour(),
        ]);
        $past1->calendarEventUsers()->attach($this->mentor->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        $past2 = CalendarEvent::factory()->create([
            'mentor_program_id' => $this->mentorProgram2->getKey(),
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => Date::yesterday()->startOfDay()->subHour(),
            'end_date_time'     => Date::yesterday()->startOfDay(),
        ]);
        $past2->calendarEventUsers()->attach($this->mentor->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        $response = new ConfirmedCalendarEventsListPage()->handle(request(), $this->mentorProgram1);
        $page = $response->toResponse(request())->getOriginalContent()->getData()['page'];
        $props = $page['props'];

        expect($props['pastCalendarEvents'])->toHaveKey($this->mentorProgram1->getKey())
            ->and($props['pastCalendarEvents'])->not->toHaveKey($this->mentorProgram2->getKey())
            ->and($props['pastCalendarEvents'][$this->mentorProgram1->getKey()]['events'])->toHaveCount(1);
    });

    it('returns all programs when no mentor program filter provided', function (): void {
        $event1 = CalendarEvent::factory()->create([
            'mentor_program_id' => $this->mentorProgram1->getKey(),
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => Date::tomorrow()->startOfDay(),
            'end_date_time'     => Date::tomorrow()->startOfDay()->addHour(),
        ]);
        $event1->calendarEventUsers()->attach($this->mentor->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        $event2 = CalendarEvent::factory()->create([
            'mentor_program_id' => $this->mentorProgram2->getKey(),
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => Date::tomorrow()->startOfDay()->addHours(2),
            'end_date_time'     => Date::tomorrow()->startOfDay()->addHours(3),
        ]);
        $event2->calendarEventUsers()->attach($this->mentor->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        $response = new ConfirmedCalendarEventsListPage()->handle(request());
        $page = $response->toResponse(request())->getOriginalContent()->getData()['page'];
        $props = $page['props'];

        // upcomingCalendarEvents should include Program X (beforeEach), Program A, and Program B
        expect($props['upcomingCalendarEvents'])
            ->toHaveKey($this->program->getKey())
            ->toHaveKey($this->mentorProgram1->getKey())
            ->toHaveKey($this->mentorProgram2->getKey());
    });

    it('orders upcomingCalendarEvents by start_date_time ascending', function (): void {
        $laterEvent = CalendarEvent::factory()->create([
            'mentor_program_id' => $this->mentorProgram1->getKey(),
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => Date::now()->addDays(5),
            'end_date_time'     => Date::now()->addDays(5)->addHour(),
        ]);
        $laterEvent->calendarEventUsers()->attach($this->mentor->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        $earlierEvent = CalendarEvent::factory()->create([
            'mentor_program_id' => $this->mentorProgram1->getKey(),
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => Date::now()->addDays(2),
            'end_date_time'     => Date::now()->addDays(2)->addHour(),
        ]);
        $earlierEvent->calendarEventUsers()->attach($this->mentor->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        $response = new ConfirmedCalendarEventsListPage()->handle(request());
        $page = $response->toResponse(request())->getOriginalContent()->getData()['page'];
        $props = $page['props'];

        $events = $props['upcomingCalendarEvents'][$this->mentorProgram1->getKey()]['events'];

        expect($events[0]['id'])->toBe($earlierEvent->getKey())
            ->and($events[1]['id'])->toBe($laterEvent->getKey());
    });

    it('orders pastCalendarEvents by start_date_time descending', function (): void {
        $olderEvent = CalendarEvent::factory()->create([
            'mentor_program_id' => $this->mentorProgram1->getKey(),
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => Date::now()->subDays(5),
            'end_date_time'     => Date::now()->subDays(5)->addHour(),
        ]);
        $olderEvent->calendarEventUsers()->attach($this->mentor->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        $recentPastEvent = CalendarEvent::factory()->create([
            'mentor_program_id' => $this->mentorProgram1->getKey(),
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => Date::now()->subDays(2),
            'end_date_time'     => Date::now()->subDays(2)->addHour(),
        ]);
        $recentPastEvent->calendarEventUsers()->attach($this->mentor->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        $response = new ConfirmedCalendarEventsListPage()->handle(request());
        $page = $response->toResponse(request())->getOriginalContent()->getData()['page'];
        $props = $page['props'];

        $events = $props['pastCalendarEvents'][$this->mentorProgram1->getKey()]['events'];

        expect($events[0]['id'])->toBe($recentPastEvent->getKey())
            ->and($events[1]['id'])->toBe($olderEvent->getKey());
    });
});
