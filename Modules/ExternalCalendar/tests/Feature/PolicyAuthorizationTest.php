<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Queue;
use Modules\Calendar\Enums\CalendarEventColoursEnum;
use Modules\Calendar\Enums\CalendarEventRoleEnum;
use Modules\Calendar\Models\CalendarEvent;
use Modules\ExternalCalendar\Enums\CalendarProviderEnum;
use Modules\ExternalCalendar\Models\ExternalCalendarEvent;
use Modules\ExternalCalendar\Models\UserCalendarIntegration;
use Modules\MentorProgram\Models\MentorProgram;

use function Pest\Laravel\actingAs;

describe('ExternalCalendar policy registration (Gate::policy 403 regression)', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        config(['calendar.encryption_key1' => base64_encode(random_bytes(32))]);

        $this->owner = User::factory()->create();
        $mentorProgram = MentorProgram::factory()->create(['mentor_id' => $this->owner->getKey()]);

        $this->calendarEvent = CalendarEvent::factory()->create([
            'mentor_program_id' => $mentorProgram->getKey(),
            'start_date_time'   => Date::tomorrow()->setTime(10, 0),
            'end_date_time'     => Date::tomorrow()->setTime(11, 0),
        ]);

        // ExternalCalendar's `->can('view', 'calendarEvent')` route middleware delegates
        // to Modules\Calendar's own CalendarEventPolicy::view, which requires attachment
        // via calendarEventUsers — not just mentor-program ownership.
        $this->calendarEvent->calendarEventUsers()->attach($this->owner->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);
    });

    describe('ExternalCalendarEventPolicy::sync via external-calendar.rerun', function (): void {
        beforeEach(function (): void {
            $this->externalEvent = ExternalCalendarEvent::factory()->create([
                'calendar_event_id' => $this->calendarEvent->getKey(),
                'user_id'           => $this->owner->getKey(),
                'provider'          => CalendarProviderEnum::GOOGLE,
                'external_event_id' => 'ext-1',
            ]);

            UserCalendarIntegration::factory()->create([
                'user_id'  => $this->owner->getKey(),
                'provider' => CalendarProviderEnum::GOOGLE,
            ]);
        });

        it('forbids a user who does not own the external calendar event', function (): void {
            $intruder = User::factory()->create();

            actingAs($intruder)
                ->post(route('external-calendar.rerun', [
                    'calendarEvent'         => $this->calendarEvent->getKey(),
                    'externalCalendarEvent' => $this->externalEvent->getKey(),
                ]))
                ->assertForbidden();
        });

        it('allows the owning user to rerun the sync', function (): void {
            Queue::fake();

            actingAs($this->owner)
                ->post(route('external-calendar.rerun', [
                    'calendarEvent'         => $this->calendarEvent->getKey(),
                    'externalCalendarEvent' => $this->externalEvent->getKey(),
                ]))
                ->assertRedirect();
        });
    });

    describe('UserCalendarIntegrationPolicy::sync via external-calendar.sync-integration', function (): void {
        beforeEach(function (): void {
            $this->integration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->owner->getKey(),
                'provider' => CalendarProviderEnum::GOOGLE,
            ]);
        });

        it('forbids a user who does not own the calendar integration', function (): void {
            $intruder = User::factory()->create();

            actingAs($intruder)
                ->post(route('external-calendar.sync-integration', [
                    'calendarEvent' => $this->calendarEvent->getKey(),
                    'integration'   => $this->integration->getKey(),
                ]))
                ->assertForbidden();
        });

        it('allows the owning user to trigger a sync', function (): void {
            Queue::fake();

            actingAs($this->owner)
                ->post(route('external-calendar.sync-integration', [
                    'calendarEvent' => $this->calendarEvent->getKey(),
                    'integration'   => $this->integration->getKey(),
                ]))
                ->assertRedirect();
        });
    });
});
