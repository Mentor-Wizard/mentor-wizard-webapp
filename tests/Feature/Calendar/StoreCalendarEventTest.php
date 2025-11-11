<?php

declare(strict_types=1);

use App\Enums\CalendarEventColoursEnum;
use App\Enums\CalendarEventRoleEnum;
use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Carbon;
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

        $this->nonMentorUser = User::factory()->create();
    });

    it('creates an event successfully', function (): void {
        actingAs($this->user);
        auth()->login($this->user);

        $eventData = [
            'title'              => 'Default event',
            'fromDate'           => Carbon::today()->format('Y-m-d'),
            'fromTime'           => '09:00',
            'toDate'             => Carbon::today()->format('Y-m-d'),
            'toTime'             => '10:00',
            'type'               => CalendarEventTypeEnum::INDIVIDUAL->value,
            'description'        => 'Test description',
            'colour'             => CalendarEventColoursEnum::BLUE->value,
            'timezone'           => 'Europe/Kyiv',
        ];

        $response = $this->withoutMiddleware()
            ->post(route('pages.calendar.store'), $eventData);
        $response->assertRedirect(route('pages.calendar.index'));

        // Times are stored in UTC, so 09:00 Europe/Kyiv = 07:00 UTC (2 hour offset)
        $this->assertDatabaseHas('calendar_events', [
            'title'             => 'Default event',
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => Carbon::today()->format('Y-m-d').' 07:00:00',
            'end_date_time'     => Carbon::today()->format('Y-m-d').' 08:00:00',
            'date'              => Carbon::today()->format('Y-m-d'),
            'duration'          => 3600,
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

    it('validates input when storing event', function (): void {
        actingAs($this->user);

        $invalidData = [
            'title'             => Str::random(256),
            'fromDate'          => '2024-08-15',
            'fromTime'          => '09:00',
            'toDate'            => '2024-08-15',
            'toTime'            => '10:00',
            'type'              => 'individual',
            'description'       => Str::random(2001),
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'timezone'          => 'Europe/Kyiv',
        ];

        $response = $this->withoutMiddleware()->post(route('pages.calendar.store'), $invalidData);
        $response->assertSessionHasErrors(['fromDate', 'description', 'title', 'type']);

        $invalidData = [
            'title'              => Str::random(256),
            'fromDate'           => '2024-08-15',
            'fromTime'           => null,
            'toDate'             => null,
            'toTime'             => null,
            'type'               => 'individual',
            'colour'             => CalendarEventColoursEnum::BLUE->value,
            'description'        => Str::random(2001),
        ];

        $response = $this->withoutMiddleware()->post(route('pages.calendar.store'), $invalidData);
        $response->assertSessionHasErrors(['fromDate', 'description', 'title', 'type']);
    });

    it('adds custom error when timeslot overlaps existing event', function (): void {
        actingAs($this->user);
        auth()->login($this->user);

        // Create an existing future event for the user from 10:00 to 15:00 tomorrow
        $tomorrow = Carbon::tomorrow();
        $event = App\Models\CalendarEvent::query()->create([
            'title'           => 'Busy block',
            'status'          => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time' => $tomorrow->copy()->setTime(10, 0),
            'end_date_time'   => $tomorrow->copy()->setTime(15, 0),
            'duration'        => 5 * 3600,
            'date'            => $tomorrow->format('Y-m-d'),
            'type'            => CalendarEventTypeEnum::INDIVIDUAL->value,
            'description'     => 'Busy',
        ]);
        $event->calendarEventUsers()->attach($this->user->getKey());

        $payload = [
            'title'       => 'Overlap attempt',
            'fromDate'    => $tomorrow->format('Y-m-d'),
            'fromTime'    => '11:00', // inside busy block
            'toDate'      => $tomorrow->format('Y-m-d'),
            'toTime'      => '12:00',
            'type'        => CalendarEventTypeEnum::INDIVIDUAL->value,
            'description' => 'Should fail due to overlap',
            'colour'      => CalendarEventColoursEnum::BLUE->value,
            'timezone'    => 'UTC',
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
            'fromDate'          => Carbon::today()->format('Y-m-d'),
            'fromTime'          => '09:00',
            'toDate'            => Carbon::today()->format('Y-m-d'),
            'toTime'            => '10:00',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'description'       => 'Test description',
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'timezone'          => 'Europe/Kyiv',
        ];

        $response = $this->withoutMiddleware()
            ->post(route('pages.calendar.store'), $eventData);
        $response->assertStatus(Response::HTTP_FORBIDDEN);
    });
});
