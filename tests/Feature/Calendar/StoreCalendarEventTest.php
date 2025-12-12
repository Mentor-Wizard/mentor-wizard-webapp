<?php

declare(strict_types=1);

use App\Enums\CalendarEventColoursEnum;
use App\Enums\CalendarEventRoleEnum;
use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Enums\RoleEnum;
use App\Models\CalendarEvent;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

use function Pest\Laravel\actingAs;

describe('Calendar CalendarEvent Store Page', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        $this->user->profile->timezone = 'Europe/Kyiv';
        $this->user->profile->save();

        $this->nonMentorUser = User::factory()->create();
    });

    it('creates an event successfully', function (): void {
        actingAs($this->user);
        auth()->login($this->user);

        $eventData = [
            'title'              => 'Default event',
            'fromDate'           => Date::today()->format('Y-m-d'),
            'fromTime'           => '09:00',
            'toDate'             => Date::today()->format('Y-m-d'),
            'toTime'             => '10:00',
            'type'               => CalendarEventTypeEnum::INDIVIDUAL->value,
            'webLink'            => 'https://google.com',
            'description'        => 'Test description',
            'colour'             => CalendarEventColoursEnum::BLUE->value,
        ];

        $response = $this->withoutMiddleware()
            ->post(route('pages.calendar.store'), $eventData);
        $response->assertRedirect(route('pages.calendar.index'));

        // Times are stored in UTC, so 09:00 Europe/Kyiv = 07:00 UTC (2 hour offset)
        $this->assertDatabaseHas('calendar_events', [
            'title'             => 'Default event',
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => Date::today()->format('Y-m-d').' 07:00:00',
            'end_date_time'     => Date::today()->format('Y-m-d').' 08:00:00',
            'date'              => Date::today()->format('Y-m-d'),
            'web_link'          => 'https://google.com',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'description'       => 'Test description',
            'mentor_program_id' => null,
        ]);

        $eventId = DB::table('calendar_events')->latest()->first()->id;
        $this->assertDatabaseHas('calendar_event_user', [
            'calendar_event_id' => $eventId,
            'user_id'           => $this->user->getKey(),
            'role'              => CalendarEventRoleEnum::HOST,
        ]);
    });

    it('fails when title is missing', function (): void {
        actingAs($this->user);

        $data = [
            'fromDate'    => Date::today()->format('Y-m-d'),
            'fromTime'    => '09:00',
            'toDate'      => Date::today()->format('Y-m-d'),
            'toTime'      => '10:00',
            'type'        => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'      => CalendarEventColoursEnum::BLUE->value,
        ];

        $this->withoutMiddleware()
            ->post(route('pages.calendar.store'), $data)
            ->assertSessionHasErrors(['title']);
    });

    it('fails when title exceeds max length', function (): void {
        actingAs($this->user);
        $data = [
            'title'       => Str::random(256),
            'fromDate'    => Date::today()->format('Y-m-d'),
            'fromTime'    => '09:00',
            'toDate'      => Date::today()->format('Y-m-d'),
            'toTime'      => '10:00',
            'type'        => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'      => CalendarEventColoursEnum::BLUE->value,
        ];
        $this->withoutMiddleware()
            ->post(route('pages.calendar.store'), $data)
            ->assertSessionHasErrors(['title']);
    });

    it('fails when fromDate is missing', function (): void {
        actingAs($this->user);
        $data = [
            'title'    => 'Event',
            'fromTime' => '09:00',
            'toDate'   => Date::today()->format('Y-m-d'),
            'toTime'   => '10:00',
            'type'     => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'   => CalendarEventColoursEnum::BLUE->value,
        ];
        $this->withoutMiddleware()->post(route('pages.calendar.store'), $data)
            ->assertSessionHasErrors(['fromDate']);
    });

    it('fails when fromDate is not a valid date', function (): void {
        actingAs($this->user);
        $data = [
            'title'    => 'Event',
            'fromDate' => '2024-00---',
            'fromTime' => '09:00',
            'toDate'   => Date::today()->format('Y-m-d'),
            'toTime'   => '10:00',
            'type'     => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'   => CalendarEventColoursEnum::BLUE->value,
        ];
        $this->withoutMiddleware()->post(route('pages.calendar.store'), $data)
            ->assertSessionHasErrors(['fromDate']);
    });

    it('fails when fromDate is in the past', function (): void {
        actingAs($this->user);
        $data = [
            'title'    => 'Event',
            'fromDate' => Date::yesterday()->format('Y-m-d'),
            'fromTime' => '09:00',
            'toDate'   => Date::yesterday()->format('Y-m-d'),
            'toTime'   => '10:00',
            'type'     => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'   => CalendarEventColoursEnum::BLUE->value,
        ];
        $this->withoutMiddleware()->post(route('pages.calendar.store'), $data)
            ->assertSessionHasErrors(['fromDate']);
    });

    it('fails when toDate is missing', function (): void {
        actingAs($this->user);
        $data = [
            'title'    => 'Event',
            'fromDate' => Date::today()->format('Y-m-d'),
            'fromTime' => '09:00',
            'toTime'   => '10:00',
            'type'     => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'   => CalendarEventColoursEnum::BLUE->value,
        ];
        $this->withoutMiddleware()->post(route('pages.calendar.store'), $data)
            ->assertSessionHasErrors(['toDate']);
    });

    it('fails when toDate is not a date', function (): void {
        actingAs($this->user);
        $data = [
            'title'    => 'Event',
            'fromDate' => Date::today()->format('Y-m-d'),
            'fromTime' => '09:00',
            'toDate'   => 'bad-date',
            'toTime'   => '10:00',
            'type'     => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'   => CalendarEventColoursEnum::BLUE->value,
        ];
        $this->withoutMiddleware()->post(route('pages.calendar.store'), $data)
            ->assertSessionHasErrors(['toDate']);
    });

    it('fails when toDate is before fromDate', function (): void {
        actingAs($this->user);
        $data = [
            'title'    => 'Event',
            'fromDate' => Date::tomorrow()->format('Y-m-d'),
            'fromTime' => '09:00',
            'toDate'   => Date::today()->format('Y-m-d'),
            'toTime'   => '10:00',
            'type'     => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'   => CalendarEventColoursEnum::BLUE->value,
        ];
        $this->withoutMiddleware()->post(route('pages.calendar.store'), $data)
            ->assertSessionHasErrors(['toDate']);
    });

    it('fails when fromTime is missing', function (): void {
        actingAs($this->user);
        $data = [
            'title'    => 'Event',
            'fromDate' => Date::tomorrow()->format('Y-m-d'),
            'toDate'   => Date::tomorrow()->format('Y-m-d'),
            'toTime'   => '10:00',
            'type'     => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'   => CalendarEventColoursEnum::BLUE->value,
        ];
        $this->withoutMiddleware()->post(route('pages.calendar.store'), $data)
            ->assertSessionHasErrors(['fromTime']);
    });

    it('fails when fromTime has invalid format', function (): void {
        actingAs($this->user);
        $data = [
            'title'    => 'Event',
            'fromDate' => Date::tomorrow()->format('Y-m-d'),
            'fromTime' => '9 AM',
            'toDate'   => Date::tomorrow()->format('Y-m-d'),
            'toTime'   => '10:00',
            'type'     => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'   => CalendarEventColoursEnum::BLUE->value,
        ];
        $this->withoutMiddleware()->post(route('pages.calendar.store'), $data)
            ->assertSessionHasErrors(['fromTime']);
    });

    it('fails when toTime is missing', function (): void {
        actingAs($this->user);
        $data = [
            'title'    => 'Event',
            'fromDate' => Date::tomorrow()->format('Y-m-d'),
            'fromTime' => '09:00',
            'toDate'   => Date::tomorrow()->format('Y-m-d'),
            'type'     => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'   => CalendarEventColoursEnum::BLUE->value,
        ];
        $this->withoutMiddleware()->post(route('pages.calendar.store'), $data)
            ->assertSessionHasErrors(['toTime']);
    });

    it('fails when toTime has invalid format', function (): void {
        actingAs($this->user);
        $data = [
            'title'    => 'Event',
            'fromDate' => Date::tomorrow()->format('Y-m-d'),
            'fromTime' => '09:00',
            'toDate'   => Date::tomorrow()->format('Y-m-d'),
            'toTime'   => '10 AM',
            'type'     => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'   => CalendarEventColoursEnum::BLUE->value,
        ];
        $this->withoutMiddleware()->post(route('pages.calendar.store'), $data)
            ->assertSessionHasErrors(['toTime']);
    });

    it('fails when toTime is not after fromTime', function (): void {
        actingAs($this->user);
        $data = [
            'title'    => 'Event',
            'fromDate' => Date::tomorrow()->format('Y-m-d'),
            'fromTime' => '11:00',
            'toDate'   => Date::tomorrow()->format('Y-m-d'),
            'toTime'   => '10:00',
            'type'     => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'   => CalendarEventColoursEnum::BLUE->value,
        ];
        $this->withoutMiddleware()->post(route('pages.calendar.store'), $data)
            ->assertSessionHasErrors(['toTime']);
    });

    it('fails when type is missing', function (): void {
        actingAs($this->user);
        $data = [
            'title'    => 'Event',
            'fromDate' => Date::tomorrow()->format('Y-m-d'),
            'fromTime' => '09:00',
            'toDate'   => Date::tomorrow()->format('Y-m-d'),
            'toTime'   => '10:00',
            'colour'   => CalendarEventColoursEnum::BLUE->value,
        ];
        $this->withoutMiddleware()->post(route('pages.calendar.store'), $data)
            ->assertSessionHasErrors(['type']);
    });

    it('fails when type is invalid', function (): void {
        actingAs($this->user);
        $data = [
            'title'    => 'Event',
            'fromDate' => Date::tomorrow()->format('Y-m-d'),
            'fromTime' => '09:00',
            'toDate'   => Date::tomorrow()->format('Y-m-d'),
            'toTime'   => '10:00',
            'type'     => 'invalid_type',
            'colour'   => CalendarEventColoursEnum::BLUE->value,
        ];
        $this->withoutMiddleware()->post(route('pages.calendar.store'), $data)
            ->assertSessionHasErrors(['type']);
    });

    it('fails when colour is missing', function (): void {
        actingAs($this->user);
        $data = [
            'title'    => 'Event',
            'fromDate' => Date::tomorrow()->format('Y-m-d'),
            'fromTime' => '09:00',
            'toDate'   => Date::tomorrow()->format('Y-m-d'),
            'toTime'   => '10:00',
            'type'     => CalendarEventTypeEnum::INDIVIDUAL->value,
        ];
        $this->withoutMiddleware()->post(route('pages.calendar.store'), $data)
            ->assertSessionHasErrors(['colour']);
    });

    it('fails when description exceeds max length', function (): void {
        actingAs($this->user);
        $data = [
            'title'       => 'Event',
            'fromDate'    => Date::tomorrow()->format('Y-m-d'),
            'fromTime'    => '09:00',
            'toDate'      => Date::tomorrow()->format('Y-m-d'),
            'toTime'      => '10:00',
            'type'        => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'      => CalendarEventColoursEnum::BLUE->value,
            'description' => Str::random(2001),
        ];
        $this->withoutMiddleware()->post(route('pages.calendar.store'), $data)
            ->assertSessionHasErrors(['description']);
    });

    it('fails when webLink is invalid url', function (): void {
        actingAs($this->user);
        $data = [
            'title'       => 'Event',
            'fromDate'    => Date::tomorrow()->format('Y-m-d'),
            'fromTime'    => '09:00',
            'toDate'      => Date::tomorrow()->format('Y-m-d'),
            'toTime'      => '10:00',
            'type'        => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'      => CalendarEventColoursEnum::BLUE->value,
            'webLink'     => 'not-a-url',
        ];
        $this->withoutMiddleware()->post(route('pages.calendar.store'), $data)
            ->assertSessionHasErrors(['webLink']);
    });

    // fromDate invalid already covered above

    it('adds custom error when timeslot overlaps existing event', function (): void {
        actingAs($this->user);
        auth()->login($this->user);

        // Create an existing future event for the user from 10:00 to 15:00 tomorrow
        $tomorrow = Date::tomorrow();
        $event = CalendarEvent::query()->create([
            'title'           => 'Busy block',
            'status'          => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time' => $tomorrow->copy()->setTime(10, 0),
            'end_date_time'   => $tomorrow->copy()->setTime(15, 0),
            'date'            => $tomorrow->format('Y-m-d'),
            'type'            => CalendarEventTypeEnum::INDIVIDUAL->value,
            'description'     => 'Busy',
        ]);
        $event->calendarEventUsers()->attach($this->user->getKey());

        $payload = [
            'title'       => 'Overlap attempt',
            'fromDate'    => $tomorrow->format('Y-m-d'),
            'fromTime'    => '13:00', // inside busy block
            'toDate'      => $tomorrow->format('Y-m-d'),
            'toTime'      => '15:00',
            'type'        => CalendarEventTypeEnum::INDIVIDUAL->value,
            'description' => 'Should fail due to overlap',
            'colour'      => CalendarEventColoursEnum::BLUE->value,
        ];

        $response = $this->withoutMiddleware()->post(route('pages.calendar.store'), $payload);

        $response->assertSessionHasErrors([
            'fromDate' => 'there are another events on this time',
        ]);
    });

    it('throws 403 when a non-mentor user tries to create an event', function (): void {
        actingAs($this->nonMentorUser);
        auth()->login($this->nonMentorUser);
        $eventData = [
            'title'             => 'Default event',
            'fromDate'          => Date::today()->format('Y-m-d'),
            'fromTime'          => '09:00',
            'toDate'            => Date::today()->format('Y-m-d'),
            'toTime'            => '10:00',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'description'       => 'Test description',
            'colour'            => CalendarEventColoursEnum::BLUE->value,
        ];

        $response = $this->post(route('pages.calendar.store'), $eventData);
        $response->assertStatus(Response::HTTP_FORBIDDEN);
    });
});
