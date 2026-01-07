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
});
