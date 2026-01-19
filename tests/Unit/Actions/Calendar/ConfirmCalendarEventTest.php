<?php

declare(strict_types=1);

use App\Actions\Calendar\ConfirmCalendarEvent;
use App\Enums\CalendarEventColoursEnum;
use App\Enums\CalendarEventRoleEnum;
use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Enums\RoleEnum;
use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

mutates(ConfirmCalendarEvent::class);

describe('ConfirmCalendarEvent (Unit)', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->host = User::factory()->create();
        $this->host->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        $this->host->profile->timezone = 'Europe/Kyiv';
        $this->host->profile->save();

        $this->mentee = User::factory()->create();

        // Create mentor program for the host and ensure events reference it
        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->host->getKey(),
        ]);

        $this->event = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION,
            'start_date_time'   => Date::tomorrow()->format('Y-m-d').' 09:00:00',
            'date'              => Date::tomorrow()->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $this->event->calendarEventUsers()->attach($this->host->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);
        $this->event->calendarEventUsers()->attach($this->mentee->getKey(), [
            'role'   => CalendarEventRoleEnum::MENTI,
            'colour' => CalendarEventColoursEnum::GREEN->value,
        ]);
    });

    it('confirms as host: updates pivot and sets event status to CONFIRMED', function (): void {
        Auth::login($this->host);

        $response = new ConfirmCalendarEvent()->handle($this->mentorProgram, $this->event);

        expect($response->getStatusCode())->toBe(Response::HTTP_FOUND)
            ->and($response->getTargetUrl())->toBe(route('pages.calendar.pending'))
            ->and(session('success'))->toBe('Event was successfully confirmed.');

        // Status should become CONFIRMED for host
        expect($this->event->fresh()->status)
            ->toBe(CalendarEventStatusEnum::CONFIRMED->value);

        // Pivot confirmed_at should be set for the host
        $pivot = $this->event->fresh()->calendarEventUsers()->where('user_id', $this->host->getKey())->first()?->pivot;
        expect($pivot?->confirmed_at)->not->toBeNull();
    });

    it('confirms as mentee: only updates pivot and keeps status pending', function (): void {
        Auth::login($this->mentee);

        $response = new ConfirmCalendarEvent()->handle($this->mentorProgram, $this->event);

        expect($response->getStatusCode())->toBe(Response::HTTP_FOUND)
            ->and($response->getTargetUrl())->toBe(route('pages.calendar.pending'));

        // Status should remain pending when mentee confirms
        expect($this->event->fresh()->status)
            ->toBe(CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION->value);

        $pivot = $this->event->fresh()->calendarEventUsers()->where('user_id', $this->mentee->getKey())->first()?->pivot;
        expect($pivot?->confirmed_at)->not->toBeNull();
    });

    it('keeps status pending if CO-HOST not confirmed and shows waiting message', function (): void {
        $cohost = User::factory()->create();
        $this->event->calendarEventUsers()->attach($cohost->getKey(), [
            'role'   => CalendarEventRoleEnum::COHOST,
            'colour' => CalendarEventColoursEnum::RED->value,
        ]);

        Auth::login($this->host);

        $response = new ConfirmCalendarEvent()->handle($this->mentorProgram, $this->event);

        expect($response->getStatusCode())->toBe(Response::HTTP_FOUND)
            ->and($response->getTargetUrl())->toBe(route('pages.calendar.pending'))
            ->and(session('success'))->toBe('Event is confirmed on your side, but waiting for confirmation from CO-HOST');

        expect($this->event->fresh()->status)
            ->toBe(CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION->value);
    });
});
