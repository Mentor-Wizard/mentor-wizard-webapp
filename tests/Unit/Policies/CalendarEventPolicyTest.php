<?php

declare(strict_types=1);

use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use App\Models\User;
use App\Policies\CalendarEventPolicy;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;

mutates(CalendarEventPolicy::class);

describe('CalendarEventPolicy (Unit)', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->policy = new CalendarEventPolicy;

        $this->mentor = User::factory()->create();
        $this->viewer = User::factory()->create();
        $this->otherMentor = User::factory()->create();

        $this->program = MentorProgram::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
        ]);

        $this->event = CalendarEvent::factory()->create([
            'mentor_program_id' => $this->program->getKey(),
            'date'              => Date::tomorrow()->toDateString(),
        ]);
    });

    it('allows update for the mentor of the program', function (): void {
        expect($this->policy->update($this->mentor, $this->event))->toBeTrue();
    });

    it('denies update for non-mentor users', function (): void {
        expect($this->policy->update($this->viewer, $this->event))->toBeFalse()
            ->and($this->policy->update($this->otherMentor, $this->event))->toBeFalse();
    });

    it('denies update when event has no program', function (): void {
        $event = CalendarEvent::factory()->create(['mentor_program_id' => null]);
        expect($this->policy->update($this->mentor, $event))->toBeFalse();
    });

    it('allows confirm for the mentor of the program', function (): void {
        expect($this->policy->confirm($this->mentor, $this->event))->toBeTrue();
    });

    it('denies confirm for non-mentor users', function (): void {
        expect($this->policy->confirm($this->viewer, $this->event))->toBeFalse()
            ->and($this->policy->confirm($this->otherMentor, $this->event))->toBeFalse();
    });

    it('denies confirm when event has no program', function (): void {
        $event = CalendarEvent::factory()->create(['mentor_program_id' => null]);
        expect($this->policy->confirm($this->mentor, $event))->toBeFalse();
    });

    it('allows delete for program mentor and for attached participant (lazy query path)', function (): void {
        // Mentor can delete
        expect($this->policy->delete($this->mentor, $this->event))->toBeTrue();

        // Attach a viewer and ensure they can delete via DB check (not relationLoaded)
        $this->event->calendarEventUsers()->attach($this->viewer->getKey());
        expect($this->policy->delete($this->viewer, $this->event))->toBeTrue();
    });

    it('delete checks relationLoaded branch as well', function (): void {
        $this->event->load('calendarEventUsers');
        // Not attached user cannot delete
        expect($this->policy->delete($this->otherMentor, $this->event))->toBeFalse();

        // Attach and load again
        $this->event->calendarEventUsers()->attach($this->otherMentor->getKey());
        $this->event->load('calendarEventUsers');
        expect($this->policy->delete($this->otherMentor, $this->event))->toBeTrue();
    });

    it('view mirrors membership (relationLoaded false and true)', function (): void {
        // Lazy (DB) path
        expect($this->policy->view($this->viewer, $this->event))->toBeFalse();
        $this->event->calendarEventUsers()->attach($this->viewer->getKey());
        expect($this->policy->view($this->viewer, $this->event))->toBeTrue();

        // relationLoaded path
        $this->event->load('calendarEventUsers');
        $stranger = User::factory()->create();
        expect($this->policy->view($stranger, $this->event))->toBeFalse();
        $this->event->calendarEventUsers()->attach($stranger->getKey());
        $this->event->load('calendarEventUsers');
        expect($this->policy->view($stranger, $this->event))->toBeTrue();
    });

    test('view checks user access when calendar event users are eager loaded', function (): void {
        $user = User::factory()->create();
        $calendarEvent = CalendarEvent::factory()->create();
        $calendarEvent->calendarEventUsers()->attach($user->id);

        // Eager load the relation
        $calendarEvent->load('calendarEventUsers');

        expect($user->can('view', $calendarEvent))->toBeTrue();
    });

    test('view checks user access when calendar event users are not eager loaded', function (): void {
        $user = User::factory()->create();
        $calendarEvent = CalendarEvent::factory()->create();
        $calendarEvent->calendarEventUsers()->attach($user->id);

        // Don't load the relation - force a fresh instance
        $calendarEvent = CalendarEvent::query()->find($calendarEvent->id);

        expect($calendarEvent->relationLoaded('calendarEventUsers'))->toBeFalse();
        expect($user->can('view', $calendarEvent))->toBeTrue();
    });

    test('view denies access when user is not attached and relation is eager loaded', function (): void {
        $user = User::factory()->create();
        $calendarEvent = CalendarEvent::factory()->create();

        // Eager load the relation (will be empty)
        $calendarEvent->load('calendarEventUsers');

        expect($user->can('view', $calendarEvent))->toBeFalse();
    });

    test('view denies access when user is not attached and relation is not loaded', function (): void {
        $user = User::factory()->create();
        $calendarEvent = CalendarEvent::factory()->create();

        // Don't load the relation
        $calendarEvent = CalendarEvent::query()->find($calendarEvent->id);

        expect($calendarEvent->relationLoaded('calendarEventUsers'))->toBeFalse();
        expect($user->can('view', $calendarEvent))->toBeFalse();
    });

    test('delete uses collection when relation is eager loaded without db query', function (): void {
        $user = User::factory()->create();
        $calendarEvent = CalendarEvent::factory()->create();
        $calendarEvent->calendarEventUsers()->attach($user->id);

        $calendarEvent->load('calendarEventUsers');

        DB::enableQueryLog();
        $result = $user->can('delete', $calendarEvent);
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        expect($result)->toBeTrue();
        expect($queryCount)->toBe(2);
    });

    test('delete queries database when relation is not loaded', function (): void {
        $user = User::factory()->create();
        $calendarEvent = CalendarEvent::factory()->create();
        $calendarEvent->calendarEventUsers()->attach($user->id);

        $calendarEvent = CalendarEvent::query()->find($calendarEvent->id);

        DB::enableQueryLog();
        $result = $user->can('delete', $calendarEvent);
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        expect($result)->toBeTrue();
        expect($queryCount)->toBeGreaterThan(0);
    });

    test('view uses collection when relation is eager loaded without db query', function (): void {
        $user = User::factory()->create();
        $calendarEvent = CalendarEvent::factory()->create();
        $calendarEvent->calendarEventUsers()->attach($user->id);

        $calendarEvent->load('calendarEventUsers');

        DB::enableQueryLog();
        $result = $user->can('view', $calendarEvent);
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        expect($result)->toBeTrue();
        expect($queryCount)->toBe(1);
    });

    test('view queries database when relation is not loaded', function (): void {
        $user = User::factory()->create();
        $calendarEvent = CalendarEvent::factory()->create();
        $calendarEvent->calendarEventUsers()->attach($user->id);

        $calendarEvent = CalendarEvent::query()->find($calendarEvent->id);

        DB::enableQueryLog();
        $result = $user->can('view', $calendarEvent);
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        expect($result)->toBeTrue();
        expect($queryCount)->toBeGreaterThan(0);
    });

});
