<?php

declare(strict_types=1);

use App\Enums\MentorSessionTypeEnum;
use App\Models\MentorProgram;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Date;
use Modules\Calendar\Actions\CalendarEvent\EditCalendarEvent;
use Modules\Calendar\Enums\CalendarEventColoursEnum;
use Modules\Calendar\Enums\CalendarEventRoleEnum;
use Modules\Calendar\Enums\CalendarEventStatusEnum;
use Modules\Calendar\Enums\CalendarEventTypeEnum;
use Modules\Calendar\Http\Requests\CalendarEvent\EditCalendarEventRequest;
use Modules\Calendar\Models\CalendarEvent;

mutates(EditCalendarEvent::class);

describe('EditCalendarEvent', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        auth()->login($this->user);
        $this->user->profile->timezone = 'Europe/Kyiv';
        $this->user->profile->save();

        $this->prepareRequest = function (EditCalendarEventRequest $request): void {
            $request->setContainer(app());
            $request->setRedirector(resolve(Redirector::class));
            $request->setUserResolver(fn () => $this->user);
        };
    });

    it('syncs user relationship without detaching other users', function (): void {
        Date::setTestNow(Date::create(2025, 6, 1, 8, 0, 0, config('app.timezone')));

        // Create event with two users attached
        $otherUser = User::factory()->create();
        $mentorProgram = MentorProgram::factory()->create(
            ['mentor_id' => $this->user->getKey()]
        );
        $event = CalendarEvent::factory()->create([
            'title'             => 'Multi-User Event',
            'start_date_time'   => Date::now()->addDays(2)->setTime(10, 0, 0),
            'end_date_time'     => Date::now()->addDays(2)->setTime(11, 0, 0),
            'date'              => Date::now()->addDays(2)->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION->value,
            'mentor_program_id' => $mentorProgram->getKey(),
        ]);

        // Attach both users
        $event->calendarEventUsers()->attach($this->user->getKey(),
            ['colour'  => CalendarEventColoursEnum::BLUE->value,
                'role' => CalendarEventRoleEnum::HOST->value,
            ]);
        $event->calendarEventUsers()->attach($otherUser->getKey(),
            ['colour'  => CalendarEventColoursEnum::GREEN->value,
                'role' => CalendarEventRoleEnum::MENTI,
            ]);

        expect($event->calendarEventUsers)->toHaveCount(2);

        // Update event with new colour for current user
        $data = [
            'id'                => $event->getKey(),
            'title'             => 'Updated Event',
            'fromDate'          => Date::now()->addDays(2)->format('Y-m-d'),
            'toDate'            => Date::now()->addDays(2)->format('Y-m-d'),
            'fromTime'          => '10:00',
            'toTime'            => '11:00',
            'description'       => 'Updated desc',
            'type'              => 'Individual',
            'session_type'      => MentorSessionTypeEnum::CODE_REVIEW->value,
            'webLink'           => 'https://google.com',
            'colour'            => CalendarEventColoursEnum::RED->value,
            'mentor_program_id' => $mentorProgram->getKey(),
        ];

        $request = new EditCalendarEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);
        $request->validateResolved();

        $action = new EditCalendarEvent;
        $result = $action->handle($request, $event);

        // Refresh event and check both users are still attached
        $event->refresh();

        expect($event->calendarEventUsers)->toHaveCount(2)
            ->and($event->calendarEventUsers->pluck('id')->toArray())
            ->toContain($this->user->getKey(), $otherUser->getKey());

        // Verify current user's colour was updated
        $currentUserPivot = $event->calendarEventUsers->where('id', $this->user->getKey())->first()->pivot;
        expect($currentUserPivot->colour)->toBe(CalendarEventColoursEnum::RED->value);

        // Verify other user's colour remains unchanged
        $otherUserPivot = $event->calendarEventUsers->where('id', $otherUser->getKey())->first()->pivot;
        expect($otherUserPivot->colour)->toBe(CalendarEventColoursEnum::GREEN->value);
    });

    it('updates event details correctly', function (): void {
        Date::setTestNow(Date::create(2025, 6, 1, 8, 0, 0, config('app.timezone')));

        $mentorProgram = MentorProgram::factory()->create(
            ['mentor_id' => $this->user->getKey()]
        );
        $event = CalendarEvent::factory()->create([
            'title'             => 'Original Title',
            'start_date_time'   => Date::now()->addDays(2)->setTime(10, 0, 0),
            'end_date_time'     => Date::now()->addDays(2)->setTime(11, 0, 0),
            'date'              => Date::now()->addDays(2)->format('Y-m-d'),
            'description'       => 'Original description',
            'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION->value,
            'mentor_program_id' => $mentorProgram->getKey(),
        ]);

        $event->calendarEventUsers()->attach($this->user->getKey(),
            ['colour'  => CalendarEventColoursEnum::BLUE->value,
                'role' => CalendarEventRoleEnum::HOST->value]);

        $data = [
            'title'             => 'Updated Title',
            'fromDate'          => Date::now()->addDays(3)->format('Y-m-d'),
            'toDate'            => Date::now()->addDays(3)->format('Y-m-d'),
            'fromTime'          => '14:30',
            'toTime'            => '15:30',
            'description'       => 'Updated description',
            'type'              => 'Group',
            'session_type'      => MentorSessionTypeEnum::CODE_REVIEW->value,
            'webLink'           => 'https://google.com',
            'colour'            => CalendarEventColoursEnum::PURPLE->value,
            'mentor_program_id' => $mentorProgram->getKey(),
        ];

        $request = new EditCalendarEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);
        $request->validateResolved();

        $action = new EditCalendarEvent;
        $result = $action->handle($request, $event);

        $event->refresh();
        expect($event->web_link)->toBe('https://google.com')
            ->and($event->duration)->toBe(60);
    });

    it('updates description of event', function (): void {
        Date::setTestNow(Date::create(2025, 6, 1, 8, 0, 0, config('app.timezone')));

        $mentorProgram = MentorProgram::factory()->create(
            ['mentor_id' => $this->user->getKey()]
        );
        $event = CalendarEvent::factory()->create([
            'title'             => 'Original Title',
            'start_date_time'   => Date::now()->addDays(2)->setTime(10, 0, 0),
            'end_date_time'     => Date::now()->addDays(2)->setTime(11, 0, 0),
            'date'              => Date::now()->addDays(2)->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION->value,
            'description'       => 'Original description',
            'mentor_program_id' => $mentorProgram->getKey(),
        ]);

        $event->calendarEventUsers()->attach($this->user->getKey(),
            ['colour' => CalendarEventColoursEnum::BLUE->value]);

        $data = [
            'title'             => 'Updated Title',
            'fromDate'          => Date::now()->addDays(3)->format('Y-m-d'),
            'toDate'            => Date::now()->addDays(3)->format('Y-m-d'),
            'fromTime'          => '14:30',
            'toTime'            => '15:30',
            'description'       => null,
            'type'              => 'Group',
            'session_type'      => MentorSessionTypeEnum::CODE_REVIEW->value,
            'webLink'           => 'https://google.com',
            'colour'            => CalendarEventColoursEnum::PURPLE->value,
            'mentor_program_id' => $mentorProgram->getKey(),
        ];

        $request = new EditCalendarEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);
        $request->validateResolved();

        $action = new EditCalendarEvent;
        $action->handle($request, $event);

        $event->refresh();
        expect($event->description)->toBeNull()
            ->and($event->duration)->toBe(60);
    });

    it('no description of event in payload', function (): void {
        Date::setTestNow(Date::create(2025, 6, 1, 8, 0, 0, config('app.timezone')));

        $mentorProgram = MentorProgram::factory()->create(
            ['mentor_id' => $this->user->getKey()]
        );
        $event = CalendarEvent::factory()->create([
            'title'             => 'Original Title',
            'start_date_time'   => Date::now()->addDays(2)->setTime(10, 0, 0),
            'end_date_time'     => Date::now()->addDays(2)->setTime(11, 0, 0),
            'date'              => Date::now()->addDays(2)->format('Y-m-d'),
            'description'       => 'Original description',
            'mentor_program_id' => $mentorProgram->getKey(),
        ]);

        $event->calendarEventUsers()->attach($this->user->getKey(),
            ['colour' => CalendarEventColoursEnum::BLUE->value]);

        $data = [
            'title'             => 'Updated Title',
            'fromDate'          => Date::now()->addDays(3)->format('Y-m-d'),
            'toDate'            => Date::now()->addDays(3)->format('Y-m-d'),
            'fromTime'          => '14:30',
            'toTime'            => '15:30',
            'type'              => 'Group',
            'session_type'      => MentorSessionTypeEnum::CODE_REVIEW->value,
            'webLink'           => 'https://google.com',
            'colour'            => CalendarEventColoursEnum::PURPLE->value,
            'mentor_program_id' => $mentorProgram->getKey(),
        ];

        $request = new EditCalendarEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);
        $request->validateResolved();

        $action = new EditCalendarEvent;
        $action->handle($request, $event);

        $event->refresh();
        expect($event->description)->toBe('Original description')
            ->and($event->duration)->toBe(60);
    });

    it('throws exception when event does not exist', function (): void {
        $event = new CalendarEvent;
        $event->id = 999;
        // Don't save, so exists will be false

        $data = [
            'title'       => 'Test',
            'fromDate'    => Date::now()->addDays(2)->format('Y-m-d'),
            'toDate'      => Date::now()->addDays(2)->format('Y-m-d'),
            'fromTime'    => '10:00',
            'toTime'      => '11:00',
            'description' => 'desc',
            'type'        => 'Individual',
            'webLink'     => 'https://google.com',
            'colour'      => CalendarEventColoursEnum::BLUE->value,
        ];

        $request = new EditCalendarEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);

        $action = new EditCalendarEvent;

        try {
            $action->handle($request, $event);
            $this->fail('Expected exception was not thrown');
        } catch (Throwable $throwable) {
            expect($throwable)->toBeInstanceOf(Error::class);
        }
    });

    it('rejects editing a CANCELLED calendar event with correct error message', function (): void {
        Date::setTestNow(Date::create(2025, 6, 1, 8, 0, 0, config('app.timezone')));

        $mentorProgram = MentorProgram::factory()->create(
            ['mentor_id' => $this->user->getKey()]
        );
        $event = CalendarEvent::factory()->create([
            'title'             => 'Cancelled Event',
            'start_date_time'   => Date::now()->addDays(2)->setTime(10, 0, 0),
            'end_date_time'     => Date::now()->addDays(2)->setTime(11, 0, 0),
            'date'              => Date::now()->addDays(2)->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CANCELLED->value,
            'mentor_program_id' => $mentorProgram->getKey(),
        ]);

        $event->calendarEventUsers()->attach($this->user->getKey(),
            ['colour' => CalendarEventColoursEnum::BLUE->value]);

        $data = [
            'title'             => 'Updated Title',
            'fromDate'          => Date::now()->addDays(3)->format('Y-m-d'),
            'toDate'            => Date::now()->addDays(3)->format('Y-m-d'),
            'fromTime'          => '14:30',
            'toTime'            => '15:30',
            'description'       => 'Updated description',
            'type'              => 'Individual',
            'session_type'      => MentorSessionTypeEnum::CODE_REVIEW->value,
            'webLink'           => 'https://google.com',
            'colour'            => CalendarEventColoursEnum::PURPLE->value,
            'mentor_program_id' => $mentorProgram->getKey(),
        ];

        $request = new EditCalendarEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);
        $request->validateResolved();

        $action = new EditCalendarEvent;
        $result = $action->handle($request, $event);

        // Should redirect with error
        expect($result->getSession()->get('error'))
            ->toBe(CalendarEventStatusEnum::CANCELLED->value.' calendar event cannot be edited');

        // Verify event was NOT updated
        $event->refresh();
        expect($event->title)->toBe('Cancelled Event');
    });

    it('rejects editing a FINISHED calendar event with correct error message', function (): void {
        Date::setTestNow(Date::create(2025, 6, 1, 8, 0, 0, config('app.timezone')));

        $mentorProgram = MentorProgram::factory()->create(
            ['mentor_id' => $this->user->getKey()]
        );
        $event = CalendarEvent::factory()->create([
            'title'             => 'Finished Event',
            'start_date_time'   => Date::now()->addDays(2)->setTime(10, 0, 0),
            'end_date_time'     => Date::now()->addDays(2)->setTime(11, 0, 0),
            'date'              => Date::now()->addDays(2)->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::FINISHED->value,
            'mentor_program_id' => $mentorProgram->getKey(),
        ]);

        $event->calendarEventUsers()->attach($this->user->getKey(),
            ['colour' => CalendarEventColoursEnum::BLUE->value]);

        $data = [
            'title'             => 'Updated Title',
            'fromDate'          => Date::now()->addDays(3)->format('Y-m-d'),
            'toDate'            => Date::now()->addDays(3)->format('Y-m-d'),
            'fromTime'          => '14:30',
            'toTime'            => '15:30',
            'description'       => 'Updated description',
            'type'              => 'Individual',
            'session_type'      => MentorSessionTypeEnum::CODE_REVIEW->value,
            'webLink'           => 'https://google.com',
            'colour'            => CalendarEventColoursEnum::PURPLE->value,
            'mentor_program_id' => $mentorProgram->getKey(),
        ];

        $request = new EditCalendarEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);
        $request->validateResolved();

        $action = new EditCalendarEvent;
        $result = $action->handle($request, $event);

        // Should redirect with error
        expect($result->getSession()->get('error'))
            ->toBe(CalendarEventStatusEnum::FINISHED->value.' calendar event cannot be edited');

        // Verify event was NOT updated
        $event->refresh();
        expect($event->title)->toBe('Finished Event');
    });

    it('error message includes status and correct text for CANCELLED events', function (): void {
        Date::setTestNow(Date::create(2025, 6, 1, 8, 0, 0, config('app.timezone')));

        $mentorProgram = MentorProgram::factory()->create(
            ['mentor_id' => $this->user->getKey()]
        );
        $event = CalendarEvent::factory()->create([
            'title'             => 'Cancelled Event',
            'start_date_time'   => Date::now()->addDays(2)->setTime(10, 0, 0),
            'end_date_time'     => Date::now()->addDays(2)->setTime(11, 0, 0),
            'date'              => Date::now()->addDays(2)->format('Y-m-d'),
            'status'            => CalendarEventStatusEnum::CANCELLED->value,
            'mentor_program_id' => $mentorProgram->getKey(),
        ]);

        $event->calendarEventUsers()->attach($this->user->getKey(),
            ['colour' => CalendarEventColoursEnum::BLUE->value]);

        $data = [
            'title'             => 'Updated Title',
            'fromDate'          => Date::now()->addDays(3)->format('Y-m-d'),
            'toDate'            => Date::now()->addDays(3)->format('Y-m-d'),
            'fromTime'          => '14:30',
            'toTime'            => '15:30',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::CODE_REVIEW->value,
            'colour'            => CalendarEventColoursEnum::PURPLE->value,
            'mentor_program_id' => $mentorProgram->getKey(),
        ];

        $request = new EditCalendarEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);
        $request->validateResolved();

        $action = new EditCalendarEvent;
        $result = $action->handle($request, $event);

        $errorMessage = $result->getSession()->get('error');

        // Verify error message contains status at the beginning
        expect($errorMessage)->toStartWith(CalendarEventStatusEnum::CANCELLED->value);

        // Verify error message contains the text part
        expect($errorMessage)->toContain('calendar event cannot be edited');

        // Verify the full message format (status + text)
        expect($errorMessage)->toBe(CalendarEventStatusEnum::CANCELLED->value.' calendar event cannot be edited');
    });
});
