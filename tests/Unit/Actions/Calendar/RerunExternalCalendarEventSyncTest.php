<?php

declare(strict_types=1);

use App\Actions\Calendar\ExternalCalendar\RerunExternalCalendarEventSync;
use App\Enums\CalendarProviderEnum;
use App\Jobs\CreateExternalCalendarEvent;
use App\Jobs\UpdateExternalCalendarEvent;
use App\Models\CalendarEvent;
use App\Models\ExternalCalendarEvent;
use App\Models\MentorProgram;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Queue;

mutates(RerunExternalCalendarEventSync::class);

describe('RerunExternalCalendarEventSync', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        config(['calendar.encryption_key1' => base64_encode(random_bytes(32))]);

        $this->user = User::factory()->create();
        $mentorProgram = MentorProgram::factory()->create(['mentor_id' => $this->user->getKey()]);

        $this->calendarEvent = CalendarEvent::factory()->create([
            'mentor_program_id' => $mentorProgram->getKey(),
            'start_date_time'   => Date::tomorrow()->setTime(10, 0),
            'end_date_time'     => Date::tomorrow()->setTime(11, 0),
        ]);

        $this->integration = UserCalendarIntegration::factory()->create([
            'user_id'  => $this->user->getKey(),
            'provider' => CalendarProviderEnum::Google,
        ]);
    });

    it('dispatches CreateExternalCalendarEvent when the external event has no external_event_id', function (): void {
        Queue::fake();

        $externalEvent = ExternalCalendarEvent::query()->create([
            'calendar_event_id' => $this->calendarEvent->getKey(),
            'user_id'           => $this->user->getKey(),
            'provider'          => CalendarProviderEnum::Google,
            'external_event_id' => null,
        ]);

        $action = new RerunExternalCalendarEventSync;
        $response = $action->handle($this->calendarEvent, $externalEvent);

        Queue::assertPushed(
            CreateExternalCalendarEvent::class,
            fn (CreateExternalCalendarEvent $job): bool => $job->calendarEvent->getKey() === $this->calendarEvent->getKey()
                && $job->integration->getKey() === $this->integration->getKey()
        );
        Queue::assertNotPushed(UpdateExternalCalendarEvent::class);

        expect($response)->toBeInstanceOf(RedirectResponse::class)
            ->and($response->getSession()->get('success'))->toBe('Sync has been queued.');
    });

    it('dispatches UpdateExternalCalendarEvent when the external event has an existing external_event_id', function (): void {
        Queue::fake();

        $externalEvent = ExternalCalendarEvent::query()->create([
            'calendar_event_id' => $this->calendarEvent->getKey(),
            'user_id'           => $this->user->getKey(),
            'provider'          => CalendarProviderEnum::Google,
            'external_event_id' => 'existing-ext-id',
        ]);

        $action = new RerunExternalCalendarEventSync;
        $action->handle($this->calendarEvent, $externalEvent);

        Queue::assertPushed(
            UpdateExternalCalendarEvent::class,
            fn (UpdateExternalCalendarEvent $job): bool => $job->calendarEvent->getKey() === $this->calendarEvent->getKey()
                && $job->externalEvent->getKey() === $externalEvent->getKey()
                && $job->integration->getKey() === $this->integration->getKey()
        );
        Queue::assertNotPushed(CreateExternalCalendarEvent::class);
    });
});
