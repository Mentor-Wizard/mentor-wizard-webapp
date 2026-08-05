<?php

declare(strict_types=1);

use App\Actions\Calendar\CalendarEvent\SyncCalendarEventToIntegration;
use App\Enums\CalendarProviderEnum;
use App\Jobs\CreateExternalCalendarEvent;
use App\Models\MentorProgram;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Queue;
use Modules\Calendar\Models\CalendarEvent;

describe('SyncCalendarEventToIntegration', function (): void {
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
            'provider' => CalendarProviderEnum::GOOGLE,
        ]);
    });

    it('dispatches a CreateExternalCalendarEvent job with the correct models', function (): void {
        Queue::fake();

        $action = new SyncCalendarEventToIntegration;
        $action->handle($this->calendarEvent, $this->integration);

        Queue::assertPushed(
            CreateExternalCalendarEvent::class,
            fn (CreateExternalCalendarEvent $job): bool => $job->calendarEvent->getKey() === $this->calendarEvent->getKey()
                && $job->integration->getKey() === $this->integration->getKey()
        );
    });

    it('returns a redirect response', function (): void {
        Queue::fake();

        $action = new SyncCalendarEventToIntegration;
        $response = $action->handle($this->calendarEvent, $this->integration);

        expect($response)->toBeInstanceOf(RedirectResponse::class);
    });

    it('includes a success flash message in the redirect', function (): void {
        Queue::fake();

        $action = new SyncCalendarEventToIntegration;
        $response = $action->handle($this->calendarEvent, $this->integration);

        expect($response->getSession()->get('success'))->toBe('Sync queued.');
    });
});
