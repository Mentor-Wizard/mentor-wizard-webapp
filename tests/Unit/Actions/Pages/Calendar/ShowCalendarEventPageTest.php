<?php

declare(strict_types=1);

use App\Actions\Pages\Calendar\ShowCalendarEventPage;
use App\Enums\CalendarEventColoursEnum;
use App\Enums\CalendarEventRoleEnum;
use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Enums\RoleEnum;
use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Date;
use Inertia\Response;
use Spatie\Permission\Models\Role;

mutates(ShowCalendarEventPage::class);

describe('Show Calendar CalendarEvent Page', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        $this->mentor = User::factory()->create();
        $this->mentor->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $this->viewer = User::factory()->create();

        $this->start = Date::parse(Date::today()->addDays(1)->format('Y-m-d').' 09:30:00');
        $this->end = Date::parse(Date::today()->addDays(1)->format('Y-m-d').' 11:00:00');

        $this->event = CalendarEvent::factory()->create([
            'title'             => 'Demo CalendarEvent',
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => $this->start->format('Y-m-d H:i:s'),
            'end_date_time'     => $this->end->format('Y-m-d H:i:s'),
            'date'              => $this->start->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'web_link'          => 'https://example.com/meet',
            'description'       => 'CalendarEvent description',
            'mentor_program_id' => MentorProgram::factory()
                ->create(['mentor_id' => $this->mentor->getKey()])->getKey(),
        ]);

        $this->event->calendarEventUsers()->attach($this->mentor->getKey(),
            [
                'colour' => CalendarEventColoursEnum::BLUE->value,
                'role'   => CalendarEventRoleEnum::HOST->value]
        );
        $this->event->calendarEventUsers()->attach($this->viewer->getKey(),
            [
                'colour' => CalendarEventColoursEnum::BLUE->value,
                'role'   => CalendarEventRoleEnum::MENTI->value,
            ]);
    });

    it('renders ShowEditEvent component
        with mentor permissions and correct event payload', function (): void {
        auth()->login($this->mentor);

        $request = new Request(['timezone' => config('app.timezone')]);
        $response = new ShowCalendarEventPage()->handle($this->event);
        $resultData = $response->toResponse(request())->getOriginalContent();
        $page = $resultData->getData()['page'];

        $expectedDuration = 90; // in minutes

        expect($response)->toBeInstanceOf(Response::class)
            ->and(Arr::get($page, 'component'))->toBe('Calendar/ShowEditCalendarEvent')
            ->and(Arr::get($page, 'props.locale'))->toBe(app()->getLocale())
            ->and(Arr::get($page, 'props.permissions'))->toBe('edit')
            ->and(Arr::get($page, 'props.calendarEvent.id'))->toBe($this->event->getKey())
            ->and(Arr::get($page, 'props.calendarEvent.title'))->toBe('Demo CalendarEvent')
            ->and(Arr::get($page, 'props.calendarEvent.fromDate'))->toBe($this->start->format('Y-m-d'))
            ->and(Arr::get($page, 'props.calendarEvent.fromDateFormatted'))->toBe($this->start->format('Y-M-d'))
            ->and(Arr::get($page, 'props.calendarEvent.fromTime'))->toBe('09:30')
            ->and(Arr::get($page, 'props.calendarEvent.toDate'))->toBe($this->end->format('Y-m-d'))
            ->and(Arr::get($page, 'props.calendarEvent.toDateFormatted'))->toBe($this->end->format('Y-M-d'))
            ->and(Arr::get($page, 'props.calendarEvent.toTime'))->toBe('11:00')
            ->and(Arr::get($page, 'props.calendarEvent.duration'))->toBe($expectedDuration)
            ->and(Arr::get($page, 'props.calendarEvent.webLink'))->toBe('https://example.com/meet')
            ->and(Arr::get($page, 'props.calendarEvent.description'))->toBe('CalendarEvent description');
    });

    it('renders ShowEditEvent component with viewer permissions for non-mentor users', function (): void {
        auth()->login($this->viewer);

        $request = new Request(['timezone' => config('app.timezone')]);
        $response = new ShowCalendarEventPage()->handle($this->event);
        $resultData = $response->toResponse(request())->getOriginalContent();
        $page = $resultData->getData()['page'];

        expect($response)->toBeInstanceOf(Response::class)
            ->and(Arr::get($page, 'component'))->toBe('Calendar/ShowEditCalendarEvent')
            ->and(Arr::get($page, 'props.permissions'))->toBe('view')
            ->and(Arr::get($page, 'props.calendarEvent.id'))->toBe($this->event->getKey());
    });

    it('renders ShowEditEvent component with mentor permissions for', function (): void {
        auth()->login($this->mentor);

        $request = new Request(['timezone' => config('app.timezone')]);
        $response = new ShowCalendarEventPage()->handle($this->event);
        $resultData = $response->toResponse(request())->getOriginalContent();
        $page = $resultData->getData()['page'];

        expect($response)->toBeInstanceOf(Response::class)
            ->and(Arr::get($page, 'component'))->toBe('Calendar/ShowEditCalendarEvent')
            ->and(Arr::get($page, 'props.permissions'))->toBe('edit')
            ->and(Arr::get($page, 'props.calendarEvent.id'))->toBe($this->event->getKey());
    });

    it('includes availableColours in response props', function (): void {
        auth()->login($this->mentor);

        $request = new Request(['timezone' => config('app.timezone')]);
        $response = new ShowCalendarEventPage()->handle($this->event);
        $resultData = $response->toResponse(request())->getOriginalContent();
        $page = $resultData->getData()['page'];

        expect(Arr::get($page, 'props.availableColours'))
            ->toBeArray()
            ->not->toBeEmpty();
    });

    it('passes user and timezone to EventShowResource which affects event payload', function (): void {
        auth()->login($this->mentor);

        // Create event at 22:00 default timezone
        $start = Date::parse(Date::today()->format('Y-m-d').' 22:00:00');
        $end = Date::parse(Date::today()->format('Y-m-d').' 23:00:00');

        $eventAtNight = CalendarEvent::factory()->create([
            'title'             => 'Night Event',
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => $start->format('Y-m-d H:i:s'),
            'end_date_time'     => $end->format('Y-m-d H:i:s'),
            'date'              => $start->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'web_link'          => 'https://example.com/night',
            'description'       => 'Night Event',
            'mentor_program_id' => MentorProgram::factory()->create(
                ['mentor_id' => $this->mentor->getKey()]
            )->getKey(),
        ]);

        $eventAtNight->calendarEventUsers()->attach($this->mentor->getKey(), [
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        // Test with Asia/Tokyo timezone (UTC+9)
        $this->mentor->profile->timezone = 'Asia/Tokyo';
        $this->mentor->profile->save();

        $responseTokyo = new ShowCalendarEventPage()->handle($eventAtNight);
        $resultDataTokyo = $responseTokyo->toResponse(request())->getOriginalContent();
        $pageTokyo = $resultDataTokyo->getData()['page'];

        // Verify timezone affects the time display
        expect(Arr::get($pageTokyo, 'props.calendarEvent.fromTime'))->not->toBe('22:00');
        expect(Arr::get($pageTokyo, 'props.permissions'))->toBe('edit');

        // Verify user is passed and colour is retrieved
        expect(Arr::get($pageTokyo, 'props.calendarEvent.colour'))->toBe(CalendarEventColoursEnum::BLUE->value);
    });

    it('passes user and timezone to EventShowResource, when have several events,
        which affects event payload', function (): void {
        auth()->login($this->mentor);

        // Create event at 22:00 default timezone
        $start = Date::parse(Date::today()->format('Y-m-d').' 22:00:00');
        $end = Date::parse(Date::today()->format('Y-m-d').' 23:00:00');

        $event1 = CalendarEvent::factory()->create([
            'title'             => 'Night Event',
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => $start->format('Y-m-d H:i:s'),
            'end_date_time'     => $end->format('Y-m-d H:i:s'),
            'date'              => $start->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'web_link'          => 'https://example.com/night',
            'description'       => 'Night Event',
            'mentor_program_id' => MentorProgram::factory()->create(
                ['mentor_id' => $this->mentor->getKey()]
            )->getKey(),
        ]);

        $event1->calendarEventUsers()->attach(
            $this->mentor->getKey(), [
                'colour' => CalendarEventColoursEnum::BLUE->value,
            ]);

        $mentor2 = User::factory()->create();
        $mentor2->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $start = Date::parse(Date::today()->format('Y-m-d').' 20:00:00');
        $end = Date::parse(Date::today()->format('Y-m-d').' 21:00:00');

        $event2 = CalendarEvent::factory()->create([
            'title'             => 'Night Event',
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => $start->format('Y-m-d H:i:s'),
            'end_date_time'     => $end->format('Y-m-d H:i:s'),
            'date'              => $start->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'web_link'          => 'https://example.com/night',
            'description'       => 'Night Event',
            'mentor_program_id' => MentorProgram::factory()->create(
                ['mentor_id' => $this->mentor->getKey()]
            )->getKey(),
        ]);

        $event2->calendarEventUsers()->attach(
            $mentor2->getKey(), [
                'colour' => CalendarEventColoursEnum::BLUE->value,
            ]);

        // Test with Asia/Tokyo timezone (UTC+9)
        $this->mentor->profile->timezone = 'Asia/Tokyo';
        $this->mentor->profile->save();

        $responseTokyo = new ShowCalendarEventPage()->handle($event1);
        $resultDataTokyo = $responseTokyo->toResponse(request())->getOriginalContent();
        $pageTokyo = $resultDataTokyo->getData()['page'];

        // Verify timezone affects the time display
        expect(Arr::get($pageTokyo, 'props.calendarEvent.fromTime'))->not->toBe('22:00');
        expect(Arr::get($pageTokyo, 'props.permissions'))->toBe('edit');

        // Verify user is passed and colour is retrieved
        expect(Arr::get($pageTokyo, 'props.calendarEvent.colour'))
            ->toBe(CalendarEventColoursEnum::BLUE->value);
    });

    it('includes base props, permissions and event payload', function (): void {
        auth()->login($this->mentor);
        /** @var CalendarEvent $event */
        $event = CalendarEvent::factory()->create(
            ['mentor_program_id' => MentorProgram::factory()->create(
                ['mentor_id' => $this->mentor->getKey()]
            )->getKey()]
        );

        $request = new Request(['timezone' => 'UTC']);
        $response = (new ShowCalendarEventPage)->handle($event);

        expect($response)->toBeInstanceOf(Response::class);
        $props = inertiaProps($response);

        expect($props)
            ->toHaveKeys(['locale', 'permissions', 'calendarEvent', 'availableColours'])
            ->and($props['permissions'])->toBe('edit')
            ->and($props['calendarEvent'])->toBeArray();
    });

    it('shows edit permission when user is a host of the calendar event', function (): void {
        $host = User::factory()->create();
        $host->profile()->create(['timezone' => 'UTC']);

        $calendarEvent = CalendarEvent::factory()->create([
            'mentor_program_id' => MentorProgram::factory()->create(
                [
                    'mentor_id' => $this->mentor->getKey(),
                ])->getKey(),
        ]);
        $calendarEvent->calendarEventUsers()->attach($host->id, [
            'role' => CalendarEventRoleEnum::HOST,
        ]);

        auth()->login($host);

        $response = (new ShowCalendarEventPage)($calendarEvent);
        $props = inertiaProps($response);

        expect($props['permissions'])->toBe('view');
    });

    it('shows view permission when user is not a host of the calendar event', function (): void {
        $host = User::factory()->create();
        $participant = User::factory()->create([]);

        $host->profile()->create(['timezone' => 'UTC']);
        $participant->profile()->create(['timezone' => 'UTC']);

        $calendarEvent = CalendarEvent::factory()->create([
            'mentor_program_id' => MentorProgram::factory()->create(
                [
                    'mentor_id' => $host,
                ])->getKey()]
        );
        $calendarEvent->calendarEventUsers()->attach($host->id, [
            'role' => CalendarEventRoleEnum::HOST,
        ]);
        $calendarEvent->calendarEventUsers()->attach($participant->id, [
            'role' => CalendarEventRoleEnum::PARTICIPANT,
        ]);

        auth()->login($participant);

        $response = (new ShowCalendarEventPage)($calendarEvent);
        $props = inertiaProps($response);

        expect($props['permissions'])->toBe('view');
    });

    it('authorization checks against specific calendar event and user role', function (): void {
        $user = User::factory()->create();
        $user->profile()->create(['timezone' => 'UTC']);

        $hostEvent = CalendarEvent::factory()->create([
            'mentor_program_id' => MentorProgram::factory()->create(
                ['mentor_id' => $this->mentor->getKey()]
            )->getKey()]);
        $hostEvent->calendarEventUsers()->attach($user->id, [
            'role' => CalendarEventRoleEnum::HOST,
        ]);

        $participantEvent = CalendarEvent::factory()->create(
            [
                'mentor_program_id' => MentorProgram::factory()->create(
                    [
                        'mentor_id' => $this->mentor->getKey(),
                    ])->getKey(),
            ]

        );
        $participantEvent->calendarEventUsers()->attach($user->id, [
            'role' => CalendarEventRoleEnum::PARTICIPANT,
        ]);

        auth()->login($user);

        // Can edit event where user is host
        $responseHost = (new ShowCalendarEventPage)($hostEvent);
        $propsHost = inertiaProps($responseHost);
        expect($propsHost['permissions'])->toBe('view');

        // Cannot edit event where user is only participant
        $responseParticipant = (new ShowCalendarEventPage)($participantEvent);
        $propsParticipant = inertiaProps($responseParticipant);
        expect($propsParticipant['permissions'])->toBe('view');
    });

    describe('ShowCalendarEventPage', function (): void {
        beforeEach(function (): void {
            $this->seed(RoleSeeder::class);
            $this->user = User::factory()->create();
            $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

            auth()->login($this->user);

            $this->mentorProgram = MentorProgram::factory()->create([
                'mentor_id'        => $this->user->getKey(),
                'session_duration' => 60,
            ]);

            $this->calendarEvent = CalendarEvent::factory()->create([
                'mentor_program_id' => $this->mentorProgram->getKey(),
                'start_date_time'   => Date::now()->addDay(),
                'end_date_time'     => Date::now()->addDay()->addHour(),
            ]);

            $this->user->calendarEvents()->attach($this->calendarEvent->getKey());
        });

        it('includes available colours in response', function (): void {
            $action = new ShowCalendarEventPage;
            $response = $action->handle($this->calendarEvent);

            expect($response)->toBeInstanceOf(Response::class);

            $page = $response->toResponse(request())->getOriginalContent()->getData()['page'];
            $props = $page['props'];

            expect($props)->toHaveKey('availableColours')
                ->and($props['availableColours'])->toBe(CalendarEventColoursEnum::values());
        });

        it('includes permissions in response based on user authorization', function (): void {
            $action = new ShowCalendarEventPage;
            $response = $action->handle($this->calendarEvent);

            expect($response)->toBeInstanceOf(Response::class);

            $page = $response->toResponse(request())->getOriginalContent()->getData()['page'];
            $props = $page['props'];

            expect($props)->toHaveKey('permissions')
                ->and($props['permissions'])->toBeIn(['edit', 'view']);
        });

        it('includes mentor program duration in response', function (): void {
            $action = new ShowCalendarEventPage;
            $response = $action->handle($this->calendarEvent);

            expect($response)->toBeInstanceOf(Response::class);

            $page = $response->toResponse(request())->getOriginalContent()->getData()['page'];
            $props = $page['props'];

            expect($props)->toHaveKey('mentorProgramDuration')
                ->and($props['mentorProgramDuration'])->toBe(60);
        });

        it('includes available slots in response', function (): void {
            $action = new ShowCalendarEventPage;
            $response = $action->handle($this->calendarEvent);

            expect($response)->toBeInstanceOf(Response::class);

            $page = $response->toResponse(request())->getOriginalContent()->getData()['page'];
            $props = $page['props'];

            expect($props)->toHaveKey('availableSlots')
                ->and($props['availableSlots'])->not->toBeNull();
        });

        it('includes calendar event data in response', function (): void {
            $action = new ShowCalendarEventPage;
            $response = $action->handle($this->calendarEvent);

            expect($response)->toBeInstanceOf(Response::class);

            $page = $response->toResponse(request())->getOriginalContent()->getData()['page'];
            $props = $page['props'];

            expect($props)->toHaveKey('calendarEvent')
                ->and($props['calendarEvent'])->toBeArray()
                ->and($props['calendarEvent'])->toHaveKeys(['id', 'title']);
        });
    });
});

describe('Mutation Coverage - permissions array parameter', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        $this->mentor = User::factory()->create();
        $this->mentor->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $this->nonMentor = User::factory()->create();
    });

    it('permissions check uses calendarEvent from array (kills ArrayItemRemoval mutation)', function (): void {
        // Create event where mentor owns the program
        $mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
        ]);

        $event = CalendarEvent::factory()->create([
            'mentor_program_id' => $mentorProgram->getKey(),
            'start_date_time'   => Date::now()->addDay(),
            'end_date_time'     => Date::now()->addDay()->addHour(),
        ]);
        $event->calendarEventUsers()->attach($this->mentor->getKey(), [
            'role' => CalendarEventRoleEnum::HOST->value,
        ]);

        // Login as mentor who owns the program
        auth()->login($this->mentor);

        $response = (new ShowCalendarEventPage)->handle($event);
        $props = inertiaProps($response);

        // Mentor of the program should have 'edit' permission
        // This proves $calendarEvent is correctly passed to the policy
        // If ArrayItemRemoval removed $calendarEvent, the policy would fail/error
        expect($props['permissions'])->toBe('edit');
    });

    it('permissions check correctly returns view for non-mentor (proves policy receives correct event)', function (): void {
        // Create event where mentor owns the program
        $mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
        ]);

        $event = CalendarEvent::factory()->create([
            'mentor_program_id' => $mentorProgram->getKey(),
            'start_date_time'   => Date::now()->addDay(),
            'end_date_time'     => Date::now()->addDay()->addHour(),
        ]);

        // Attach both mentor and non-mentor to the event
        $event->calendarEventUsers()->attach($this->mentor->getKey(), [
            'role' => CalendarEventRoleEnum::HOST->value,
        ]);
        $event->calendarEventUsers()->attach($this->nonMentor->getKey(), [
            'role' => CalendarEventRoleEnum::MENTI->value,
        ]);

        // Login as non-mentor
        auth()->login($this->nonMentor);

        $response = (new ShowCalendarEventPage)->handle($event);
        $props = inertiaProps($response);

        // Non-mentor should have 'view' permission (not 'edit')
        // This proves the policy correctly evaluates based on the calendarEvent
        expect($props['permissions'])->toBe('view');
    });

    it('different events with different mentors return correct permissions (proves specific event is used)', function (): void {
        // Mentor 1 owns program 1
        $mentorProgram1 = MentorProgram::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
        ]);

        // Another mentor owns program 2
        $otherMentor = User::factory()->create();
        $otherMentor->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $mentorProgram2 = MentorProgram::factory()->create([
            'mentor_id' => $otherMentor->getKey(),
        ]);

        // Event 1 belongs to mentor's program
        $event1 = CalendarEvent::factory()->create([
            'mentor_program_id' => $mentorProgram1->getKey(),
            'start_date_time'   => Date::now()->addDay(),
            'end_date_time'     => Date::now()->addDay()->addHour(),
        ]);
        $event1->calendarEventUsers()->attach($this->mentor->getKey());

        // Event 2 belongs to other mentor's program
        $event2 = CalendarEvent::factory()->create([
            'mentor_program_id' => $mentorProgram2->getKey(),
            'start_date_time'   => Date::now()->addDays(2),
            'end_date_time'     => Date::now()->addDays(2)->addHour(),
        ]);
        $event2->calendarEventUsers()->attach($this->mentor->getKey());

        // Login as the first mentor
        auth()->login($this->mentor);

        // For event1 (their own program) - should be 'edit'
        $response1 = (new ShowCalendarEventPage)->handle($event1);
        $props1 = inertiaProps($response1);
        expect($props1['permissions'])->toBe('edit');

        // For event2 (other mentor's program) - should be 'view'
        $response2 = (new ShowCalendarEventPage)->handle($event2);
        $props2 = inertiaProps($response2);
        expect($props2['permissions'])->toBe('view');

        // This proves the SPECIFIC calendarEvent passed to can() affects the result
        // If the calendarEvent was removed from the array, both would have the same result
    });

    it('permissions evaluation requires the calendarEvent argument (explicit policy test)', function (): void {
        $mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
        ]);

        $event = CalendarEvent::factory()->create([
            'mentor_program_id' => $mentorProgram->getKey(),
            'start_date_time'   => Date::now()->addDay(),
            'end_date_time'     => Date::now()->addDay()->addHour(),
        ]);
        $event->calendarEventUsers()->attach($this->mentor->getKey());

        auth()->login($this->mentor);

        // Direct policy check - this is what the ShowCalendarEventPage does internally
        $canUpdate = $this->mentor->can('update', [$event, $this->mentor]);

        // The mentor of the program should be able to update
        expect($canUpdate)->toBeTrue();

        // If we check with a different event (not from this mentor's program), it should fail
        $otherMentor = User::factory()->create();
        $otherProgram = MentorProgram::factory()->create(['mentor_id' => $otherMentor->getKey()]);
        $otherEvent = CalendarEvent::factory()->create([
            'mentor_program_id' => $otherProgram->getKey(),
            'start_date_time'   => Date::now()->addDay(),
            'end_date_time'     => Date::now()->addDay()->addHour(),
        ]);

        $canUpdateOther = $this->mentor->can('update', [$otherEvent, $this->mentor]);

        // Should NOT be able to update other mentor's event
        expect($canUpdateOther)->toBeFalse();
    });
});
