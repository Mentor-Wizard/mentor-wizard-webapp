<?php

declare(strict_types=1);

use App\Actions\Calendar\CalendarEvent\ConfirmCalendarEvent;
use App\Enums\CalendarEventColoursEnum;
use App\Enums\CalendarEventRoleEnum;
use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Enums\RoleEnum;
use App\Http\Requests\Calendar\CalendarEvent\ConfirmCalendarEventRequest;
use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use App\Models\User;
use App\Notifications\CalendarEventConfirmedNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

mutates(ConfirmCalendarEvent::class);

function makeConfirmCalendarEventRequest(User $user, MentorProgram $mentorProgram, CalendarEvent $calendarEvent): ConfirmCalendarEventRequest
{
    $request = new Request;
    $request->setRouteResolver(function () use ($mentorProgram, $calendarEvent): Route {
        $route = new Route('PATCH', 'test', fn (): null => null);
        $route->bind(new Request);
        $route->setParameter('mentorProgram', $mentorProgram);
        $route->setParameter('calendarEvent', $calendarEvent);

        return $route;
    });

    $formRequest = ConfirmCalendarEventRequest::createFromBase($request);
    $formRequest->setUserResolver(fn (?string $guard = null): User => $user);

    return $formRequest;
}

describe('ConfirmCalendarEvent (Unit)', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->host = User::factory()->create();
        $this->host->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        $this->host->profile->timezone = 'Europe/Kyiv';
        $this->host->profile->save();

        $this->mentee = User::factory()->create();

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
        $mentor = User::factory()->create();
        $mentorProgram = MentorProgram::factory()->create(['mentor_id' => $mentor->getKey()]);

        $calendarEvent = CalendarEvent::factory()->create([
            'status'          => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time' => Date::now()->addDay(),
            'end_date_time'   => Date::now()->addDay()->addHour(),
        ]);

        $calendarEvent->calendarEventUsers()->attach($mentor->getKey(), [
            'role'         => CalendarEventRoleEnum::HOST->value,
            'confirmed_at' => null,
        ]);

        $mentor->load('calendarEvents');

        $request = makeConfirmCalendarEventRequest($mentor, $mentorProgram, $calendarEvent);
        new ConfirmCalendarEvent()->handle($request, $mentorProgram, $calendarEvent);

        expect(session('error'))->not->toBe('There is another confirmed event in this time slot.');
    });

    it('confirms as host: updates pivot and sets event status to CONFIRMED', function (): void {
        $request = makeConfirmCalendarEventRequest($this->host, $this->mentorProgram, $this->event);
        $response = new ConfirmCalendarEvent()->handle($request, $this->mentorProgram, $this->event);

        expect($response->getStatusCode())->toBe(Response::HTTP_FOUND)
            ->and($response->getTargetUrl())->toBe(route('pages.calendar.pending'))
            ->and(session('success'))->toBe('Event was successfully confirmed.')
            ->and($this->event->fresh()->status)->toBe(CalendarEventStatusEnum::CONFIRMED);

        $pivot = $this->event->fresh()->calendarEventUsers()->where('user_id', $this->host->getKey())->first()?->pivot;
        expect($pivot?->confirmed_at)->not->toBeNull();
    });

    it('confirms as mentee: only updates pivot and keeps status pending', function (): void {
        $request = makeConfirmCalendarEventRequest($this->mentee, $this->mentorProgram, $this->event);
        $response = new ConfirmCalendarEvent()->handle($request, $this->mentorProgram, $this->event);

        expect($response->getStatusCode())->toBe(Response::HTTP_FOUND)
            ->and($response->getTargetUrl())->toBe(route('pages.calendar.pending'))
            ->and($this->event->fresh()->status)->toBe(CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION);

        $pivot = $this->event->fresh()->calendarEventUsers()->where('user_id', $this->mentee->getKey())->first()?->pivot;
        expect($pivot?->confirmed_at)->not->toBeNull();
    });

    it('keeps status pending if CO-HOST not confirmed and shows waiting message', function (): void {
        $cohost = User::factory()->create();
        $this->event->calendarEventUsers()->attach($cohost->getKey(), [
            'role'   => CalendarEventRoleEnum::COHOST,
            'colour' => CalendarEventColoursEnum::RED->value,
        ]);

        $request = makeConfirmCalendarEventRequest($this->host, $this->mentorProgram, $this->event);
        $response = new ConfirmCalendarEvent()->handle($request, $this->mentorProgram, $this->event);

        expect($response->getStatusCode())->toBe(Response::HTTP_FOUND)
            ->and($response->getTargetUrl())->toBe(route('pages.calendar.pending'))
            ->and(session('success'))->toBe('Event is confirmed on your side, but waiting for confirmation from CO-HOST')
            ->and($this->event->fresh()->status)->toBe(CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION);
    });

    it('rejects confirmation when exactly ONE overlapping confirmed event exists', function (): void {
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

        $request = makeConfirmCalendarEventRequest($this->host, $this->mentorProgram, $this->event);
        $response = new ConfirmCalendarEvent()->handle($request, $this->mentorProgram, $this->event);

        expect($response->getStatusCode())->toBe(Response::HTTP_FOUND)
            ->and($response->getTargetUrl())->toBe(route('pages.calendar.pending'))
            ->and(session('error'))->toBe('There is another confirmed event in this time slot.')
            ->and($this->event->fresh()->status)->toBe(CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION);
    });

    it('excludes current event from overlap check (whereNotIn with event ID)', function (): void {
        $request = makeConfirmCalendarEventRequest($this->host, $this->mentorProgram, $this->event);
        $response = new ConfirmCalendarEvent()->handle($request, $this->mentorProgram, $this->event);

        expect($response->getStatusCode())->toBe(Response::HTTP_FOUND)
            ->and($response->getTargetUrl())->toBe(route('pages.calendar.pending'))
            ->and(session('success'))->toBe('Event was successfully confirmed.')
            ->and($this->event->fresh()->status)->toBe(CalendarEventStatusEnum::CONFIRMED);
    });

    it('rejects confirmation with error when start time is in the past', function (): void {
        $this->event->update([
            'start_date_time' => Date::now()->subHour()->format('Y-m-d H:i:s'),
        ]);

        $request = makeConfirmCalendarEventRequest($this->host, $this->mentorProgram, $this->event);
        $response = new ConfirmCalendarEvent()->handle($request, $this->mentorProgram, $this->event);

        expect($response->getStatusCode())->toBe(Response::HTTP_FOUND)
            ->and($response->getTargetUrl())->toBe(route('pages.calendar.pending'))
            ->and(session('error'))->toBe('Start time for this event is already past')
            ->and($this->event->fresh()->status)->toBe(CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION);
    });

    it('cancels all overlapping pending events after confirming', function (): void {
        $overlapping1 = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION,
            'start_date_time'   => $this->event->start_date_time,
            'end_date_time'     => $this->event->end_date_time,
            'date'              => $this->event->date,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $overlapping1->calendarEventUsers()->attach($this->host->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST,
            'colour' => CalendarEventColoursEnum::RED->value,
        ]);

        $overlapping2 = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION,
            'start_date_time'   => $this->event->start_date_time->addMinutes(15),
            'end_date_time'     => $this->event->end_date_time->subMinutes(15),
            'date'              => $this->event->date,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $overlapping2->calendarEventUsers()->attach($this->host->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST,
            'colour' => CalendarEventColoursEnum::GREEN->value,
        ]);

        $request = makeConfirmCalendarEventRequest($this->host, $this->mentorProgram, $this->event);
        new ConfirmCalendarEvent()->handle($request, $this->mentorProgram, $this->event);

        expect($this->event->fresh()->status)->toBe(CalendarEventStatusEnum::CONFIRMED)
            ->and($overlapping1->fresh()->status)->toBe(CalendarEventStatusEnum::CANCELLED)
            ->and($overlapping2->fresh()->status)->toBe(CalendarEventStatusEnum::CANCELLED);
    });

    it('does not cancel pending events in non-overlapping time slots', function (): void {
        $nonOverlapping = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION,
            'start_date_time'   => $this->event->end_date_time->addHour(),
            'end_date_time'     => $this->event->end_date_time->addHours(2),
            'date'              => $this->event->date,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $nonOverlapping->calendarEventUsers()->attach($this->host->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST,
            'colour' => CalendarEventColoursEnum::RED->value,
        ]);

        $request = makeConfirmCalendarEventRequest($this->host, $this->mentorProgram, $this->event);
        new ConfirmCalendarEvent()->handle($request, $this->mentorProgram, $this->event);

        expect($this->event->fresh()->status)->toBe(CalendarEventStatusEnum::CONFIRMED)
            ->and($nonOverlapping->fresh()->status)->toBe(CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION);
    });

    it('cancels overlapping pending events across different mentor programs of the same mentor', function (): void {
        $otherProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->host->getKey(),
        ]);

        $overlappingOtherProgram = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION,
            'start_date_time'   => $this->event->start_date_time,
            'end_date_time'     => $this->event->end_date_time,
            'date'              => $this->event->date,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'mentor_program_id' => $otherProgram->getKey(),
        ]);
        $overlappingOtherProgram->calendarEventUsers()->attach($this->host->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST,
            'colour' => CalendarEventColoursEnum::RED->value,
        ]);

        $request = makeConfirmCalendarEventRequest($this->host, $this->mentorProgram, $this->event);
        new ConfirmCalendarEvent()->handle($request, $this->mentorProgram, $this->event);

        expect($this->event->fresh()->status)->toBe(CalendarEventStatusEnum::CONFIRMED)
            ->and($overlappingOtherProgram->fresh()->status)->toBe(CalendarEventStatusEnum::CANCELLED);
    });

    it('does not cancel already cancelled events in overlapping slots', function (): void {
        $alreadyCancelled = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::CANCELLED,
            'start_date_time'   => $this->event->start_date_time,
            'end_date_time'     => $this->event->end_date_time,
            'date'              => $this->event->date,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $alreadyCancelled->calendarEventUsers()->attach($this->host->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST,
            'colour' => CalendarEventColoursEnum::RED->value,
        ]);

        $request = makeConfirmCalendarEventRequest($this->host, $this->mentorProgram, $this->event);
        new ConfirmCalendarEvent()->handle($request, $this->mentorProgram, $this->event);

        expect($this->event->fresh()->status)->toBe(CalendarEventStatusEnum::CONFIRMED)
            ->and($alreadyCancelled->fresh()->status)->toBe(CalendarEventStatusEnum::CANCELLED);
    });

    it('sends confirmed event notification to mentee participants', function (): void {
        Notification::fake();

        $request = makeConfirmCalendarEventRequest($this->host, $this->mentorProgram, $this->event);
        new ConfirmCalendarEvent()->handle($request, $this->mentorProgram, $this->event);

        Notification::assertSentTo(
            $this->mentee,
            CalendarEventConfirmedNotification::class,
        );
    });

    it('redirects to pending page with success message when host confirms', function (): void {
        $request = makeConfirmCalendarEventRequest($this->host, $this->mentorProgram, $this->event);
        new ConfirmCalendarEvent()->handle($request, $this->mentorProgram, $this->event);

        expect(session('success'))->toBe('Event was successfully confirmed.');
    });

    it('does not return co-host waiting message when host is the only attendee', function (): void {
        $request = makeConfirmCalendarEventRequest($this->host, $this->mentorProgram, $this->event);
        new ConfirmCalendarEvent()->handle($request, $this->mentorProgram, $this->event);

        expect(session('success'))->not->toEqual('Event is confirmed on your side, but waiting for confirmation from CO-HOST');
    });

    it('cancels overlapping pending events but not the confirmed event itself', function (): void {
        $pending = CalendarEvent::factory()->create([
            'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION,
            'start_date_time'   => $this->event->start_date_time,
            'end_date_time'     => $this->event->end_date_time,
            'date'              => $this->event->date,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $pending->calendarEventUsers()->attach($this->host->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => CalendarEventColoursEnum::RED->value,
        ]);

        $request = makeConfirmCalendarEventRequest($this->host, $this->mentorProgram, $this->event);
        new ConfirmCalendarEvent()->handle($request, $this->mentorProgram, $this->event);

        expect($this->event->fresh()->status)->toBe(CalendarEventStatusEnum::CONFIRMED)
            ->and($pending->fresh()->status)->toBe(CalendarEventStatusEnum::CANCELLED);
    });

    it('whereNotIn must include event ID to exclude self from overlap check (kills RemoveArrayItem)', function (): void {
        $this->event->update(['status' => CalendarEventStatusEnum::CONFIRMED->value]);

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

        $request = makeConfirmCalendarEventRequest($this->host, $this->mentorProgram, $newEvent);
        $response = new ConfirmCalendarEvent()->handle($request, $this->mentorProgram, $newEvent);

        expect($response->getStatusCode())->toBe(Response::HTTP_FOUND)
            ->and(session('error'))->toBe('There is another confirmed event in this time slot.');
    });
});
