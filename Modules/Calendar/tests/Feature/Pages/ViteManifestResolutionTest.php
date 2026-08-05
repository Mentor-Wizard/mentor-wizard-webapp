<?php

declare(strict_types=1);

use App\Enums\MentorSessionTypeEnum;
use App\Enums\RoleEnum;
use App\Models\MentorProgram;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;
use Modules\Calendar\Enums\CalendarEventColoursEnum;
use Modules\Calendar\Enums\CalendarEventRoleEnum;
use Modules\Calendar\Enums\CalendarEventStatusEnum;
use Modules\Calendar\Enums\CalendarEventTypeEnum;
use Modules\Calendar\Models\CalendarEvent;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

/*
 * The rest of the suite runs with `withoutVite()` (see tests/TestCase.php), which stubs the
 * `@vite` Blade directive out entirely. That makes every "does the root Blade template
 * actually render?" failure invisible to feature tests. These tests deliberately restore the
 * real Vite handler so that a root-template/manifest mismatch surfaces as a test failure
 * instead of a production 500.
 */
describe('Root template renders against the real Vite manifest', function (): void {
    beforeEach(function (): void {
        if (! file_exists(public_path('build/manifest.json'))) {
            $this->markTestSkipped('Vite manifest missing - run `yarn build` first.');
        }

        $this->seed(RoleSeeder::class);
        $this->withVite();

        $this->mentor = User::factory()->create();
        $this->mentor->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        $this->mentor->profile->timezone = 'UTC';
        $this->mentor->profile->save();

        $this->program = MentorProgram::factory()->create([
            'mentor_id'        => $this->mentor->getKey(),
            'session_duration' => 30,
        ]);

        $this->event = CalendarEvent::factory()->create([
            'title'             => 'Manifest smoke event',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => Date::tomorrow()->format('Y-m-d').' 12:00:00',
            'end_date_time'     => Date::tomorrow()->format('Y-m-d').' 13:00:00',
            'date'              => Date::tomorrow()->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'mentor_program_id' => $this->program->getKey(),
        ]);

        $this->event->calendarEventUsers()->attach($this->mentor->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);
    });

    it('renders the calendar index page', function (): void {
        $this->actingAs($this->mentor)
            ->get(route('pages.calendar.index'))
            ->assertStatus(Response::HTTP_OK);
    });

    it('renders the calendar event detail page', function (): void {
        $this->actingAs($this->mentor)
            ->get(route('pages.calendar.show', $this->event->getKey()))
            ->assertStatus(Response::HTTP_OK);
    });

    it('renders the pending calendar events page', function (): void {
        $this->actingAs($this->mentor)
            ->get(route('pages.calendar.pending'))
            ->assertStatus(Response::HTTP_OK);
    });

    it('renders the confirmed calendar events page', function (): void {
        $this->actingAs($this->mentor)
            ->get(route('pages.calendar.confirmed'))
            ->assertStatus(Response::HTTP_OK);
    });

    it('renders the mentor program booking page', function (): void {
        $this->actingAs($this->mentor)
            ->get(route('pages.mentor.program.book', $this->program->slug))
            ->assertStatus(Response::HTTP_OK);
    });
});
