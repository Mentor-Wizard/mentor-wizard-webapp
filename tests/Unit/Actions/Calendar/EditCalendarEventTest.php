<?php

declare(strict_types=1);

use App\Actions\Calendar\EditCalendarEvent;
use App\Enums\CalendarEventColoursEnum;
use App\Http\Requests\Calendar\EditCalendarEventRequest;
use App\Models\CalendarEvent;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Date;

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
        $event = CalendarEvent::factory()->create([
            'title'           => 'Multi-User Event',
            'start_date_time' => Date::now()->addDays(2)->setTime(10, 0, 0),
            'end_date_time'   => Date::now()->addDays(2)->setTime(11, 0, 0),
            'date'            => Date::now()->addDays(2)->format('Y-m-d'),
        ]);

        // Attach both users
        $event->calendarEventUsers()->attach($this->user->getKey(), ['colour' => CalendarEventColoursEnum::BLUE->value]);
        $event->calendarEventUsers()->attach($otherUser->getKey(), ['colour' => CalendarEventColoursEnum::GREEN->value]);

        expect($event->calendarEventUsers)->toHaveCount(2);

        // Update event with new colour for current user
        $data = [
            'id'          => $event->getKey(),
            'title'       => 'Updated Event',
            'fromDate'    => Date::now()->addDays(2)->format('Y-m-d'),
            'toDate'      => Date::now()->addDays(2)->format('Y-m-d'),
            'fromTime'    => '10:00',
            'toTime'      => '11:00',
            'description' => 'Updated desc',
            'type'        => 'Individual',
            'webLink'     => 'https://google.com',
            'colour'      => CalendarEventColoursEnum::RED->value,
        ];

        $request = new EditCalendarEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);
        $request->validateResolved();

        $action = new EditCalendarEvent;
        $action->handle($request, $event);

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

        $event = CalendarEvent::factory()->create([
            'title'           => 'Original Title',
            'start_date_time' => Date::now()->addDays(2)->setTime(10, 0, 0),
            'end_date_time'   => Date::now()->addDays(2)->setTime(11, 0, 0),
            'date'            => Date::now()->addDays(2)->format('Y-m-d'),
            'description'     => 'Original description',
        ]);

        $event->calendarEventUsers()->attach($this->user->getKey(), ['colour' => CalendarEventColoursEnum::BLUE->value]);

        $data = [
            'title'       => 'Updated Title',
            'fromDate'    => Date::now()->addDays(3)->format('Y-m-d'),
            'toDate'      => Date::now()->addDays(3)->format('Y-m-d'),
            'fromTime'    => '14:00',
            'toTime'      => '15:30',
            'description' => 'Updated description',
            'type'        => 'Group',
            'webLink'     => 'https://google.com',
            'colour'      => CalendarEventColoursEnum::PURPLE->value,
        ];

        $request = new EditCalendarEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);
        $request->validateResolved();

        $action = new EditCalendarEvent;
        $action->handle($request, $event);

        $event->refresh();
        expect($event->title)->toBe('Updated Title')
            ->and($event->description)->toBe('Updated description')
            ->and($event->duration)->toBe(90); // 1.5 hours
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
});
