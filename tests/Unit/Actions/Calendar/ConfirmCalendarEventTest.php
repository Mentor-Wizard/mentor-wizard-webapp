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
            'end_date_time'     => Date::tomorrow()->format('Y-m-d').' 10:00:00',
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

    it('does not consider the event itself when checking for overlaps', function (): void {
        // Setup: Event already has CONFIRMED status but user hasn't confirmed their participation
        $mentor = User::factory()->create();
        $mentorProgram = MentorProgram::factory()->create(['mentor_id' => $mentor->id]);

        $calendarEvent = CalendarEvent::factory()->create([
            'status'          => CalendarEventStatusEnum::CONFIRMED->value, // Already confirmed
            'start_date_time' => Date::now()->addDay(),
            'end_date_time'   => Date::now()->addDay()->addHour(),
        ]);

        // Attach mentor to event (not yet confirmed on pivot)
        $calendarEvent->calendarEventUsers()->attach($mentor->id, [
            'role'         => CalendarEventRoleEnum::HOST->value,
            'confirmed_at' => null,
        ]);

        // Load mentor's calendarEvents relation
        $mentor->load('calendarEvents');

        Auth::login($mentor);

        $action = new ConfirmCalendar3Event;
        $response = $action->handle($mentorProgram, $calendarEvent);

        // Should NOT fail with overlap error (the event shouldn't find itself)
        expect(session('error'))->not->toBe('There are another confirmed event in this time slot.');
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

    it('rejects confirmation when exactly ONE overlapping confirmed event exists', function (): void {
        Auth::login($this->host);

        // Create exactly one overlapping confirmed event for the mentor
        $overlappingEvent = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => $this->event->start_date_time,
            'end_date_time'     => $this->event->end_date_time,
            'date'              => $this->event->date,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $overlappingEvent->calendarEventUsers()->attach($this->host->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST,
            'colour' => CalendarEventColoursEnum::RED->value,
        ]);

        $response = new ConfirmCalendarEvent()->handle($this->mentorProgram, $this->event);

        expect($response->getStatusCode())->toBe(Response::HTTP_FOUND)
            ->and($response->getTargetUrl())->toBe(route('pages.calendar.pending'))
            ->and(session('error'))->toBe('There are another confirmed event in this time slot.');

        // Event should NOT be confirmed
        expect($this->event->fresh()->status)
            ->toBe(CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION->value);
    });

    it('excludes current event from overlap check (whereNotIn with event ID)', function (): void {
        Auth::login($this->host);

        // The event being confirmed should NOT be counted as overlapping with itself
        // This test verifies that the current event is excluded from the whereNotIn check
        $response = new ConfirmCalendarEvent()->handle($this->mentorProgram, $this->event);

        // Should succeed since no OTHER confirmed events overlap
        expect($response->getStatusCode())->toBe(Response::HTTP_FOUND)
            ->and($response->getTargetUrl())->toBe(route('pages.calendar.pending'))
            ->and(session('success'))->toBe('Event was successfully confirmed.');

        // Status should become CONFIRMED
        expect($this->event->fresh()->status)
            ->toBe(CalendarEventStatusEnum::CONFIRMED->value);
    });

    it('rejects confirmation with error when start time is in the past', function (): void {
        Auth::login($this->host);

        // Update event to have start time in the past
        $this->event->update([
            'start_date_time' => Date::now()->subHour()->format('Y-m-d H:i:s'),
        ]);

        $response = new ConfirmCalendarEvent()->handle($this->mentorProgram, $this->event);

        expect($response->getStatusCode())->toBe(Response::HTTP_FOUND)
            ->and($response->getTargetUrl())->toBe(route('pages.calendar.pending'))
            ->and(session('error'))->toBe('Start time for this event is already past');

        // Event should NOT be confirmed
        expect($this->event->fresh()->status)
            ->toBe(CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION->value);
    });

    it('whereNotIn must include event ID to exclude self from overlap check (kills RemoveArrayItem)', function (): void {
        Auth::login($this->host);

        // Pre-confirm the event first (simulate an already confirmed event)
        $this->event->update(['status' => CalendarEventStatusEnum::CONFIRMED->value]);

        // Create a NEW pending event with the SAME time slot
        $newEvent = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION,
            'start_date_time'   => $this->event->start_date_time,
            'end_date_time'     => $this->event->end_date_time,
            'date'              => $this->event->date,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $newEvent->calendarEventUsers()->attach($this->host->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        // Now try to confirm the new event - it should fail because there's an overlapping confirmed event
        $response = new ConfirmCalendarEvent()->handle($this->mentorProgram, $newEvent);

        // The whereNotIn([$calendarEvent->id]) should exclude the new event from its own check
        // but still find the FIRST confirmed event as overlapping
        expect($response->getStatusCode())->toBe(Response::HTTP_FOUND)
            ->and(session('error'))->toBe('There are another confirmed event in this time slot.');

        // If whereNotIn([]) was used (empty array from mutation), the self-check would
        // incorrectly include the event being confirmed, potentially causing false positives
    });
});
