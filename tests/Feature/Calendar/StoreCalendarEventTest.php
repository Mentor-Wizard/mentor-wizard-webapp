<?php

declare(strict_types=1);

use App\Enums\EventRoleEnum;
use App\Enums\EventStatusEnum;
use App\Enums\EventTypeEnum;
use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

use function Pest\Laravel\actingAs;

describe('Calendar Event Store Page', function (): void {
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
            'title'             => 'Default event',
            'fromDate'          => Carbon::today()->format('Y-m-d'),
            'fromTime'          => '09:00',
            'toDate'            => Carbon::today()->format('Y-m-d'),
            'toTime'            => '10:00',
            'type'              => EventTypeEnum::INDIVIDUAL->value,
            'description'       => 'Test description',
            'timezone'          => 'Europe/Kyiv',
        ];

        $response = $this->withoutMiddleware()
            ->post(route('pages.calendar.store'), $eventData);
        $response->assertRedirect(route('pages.calendar'));

        $this->assertDatabaseHas('events', [
            'title'             => 'Default event',
            'status'            => EventStatusEnum::CONFIRMED,
            'start_date_time'   => Carbon::today()->format('Y-m-d').' 09:00:00',
            'end_date_time'     => Carbon::today()->format('Y-m-d').' 10:00:00',
            'date'              => Carbon::today()->format('Y-m-d'),
            'duration'          => 3600,
            'type'              => EventTypeEnum::INDIVIDUAL->value,
            'description'       => 'Test description',
            'mentor_program_id' => null,
        ]);

        $eventId = DB::table('events')->latest()->first()->id;
        $this->assertDatabaseHas('event_user', [
            'event_id'          => $eventId,
            'user_id'           => $this->user->getKey(),
            'role'              => EventRoleEnum::HOST,
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
            'timezone'          => 'Europe/Kyiv',
        ];

        $response = $this->withoutMiddleware()->post(route('pages.calendar.store'), $invalidData);
        $response->assertSessionHasErrors(['fromDate', 'description', 'title', 'type']);

        $invalidData = [
            'title'             => Str::random(256),
            'fromDate'          => '2024-08-15',
            'fromTime'          => null,
            'toDate'            => null,
            'toTime'            => null,
            'type'              => 'individual',
            'description'       => Str::random(2001),
        ];

        $response = $this->withoutMiddleware()->post(route('pages.calendar.store'), $invalidData);
        $response->assertSessionHasErrors(['fromDate', 'description', 'title', 'type']);
    });

    it('adds custom error when timeslot overlaps existing event', function (): void {
        actingAs($this->user);
        auth()->login($this->user);

        // Create an existing future event for the user from 10:00 to 15:00 tomorrow
        $tomorrow = Carbon::tomorrow();
        $event = App\Models\Event::query()->create([
            'title'           => 'Busy block',
            'status'          => EventStatusEnum::CONFIRMED,
            'start_date_time' => $tomorrow->copy()->setTime(10, 0),
            'end_date_time'   => $tomorrow->copy()->setTime(15, 0),
            'duration'        => 5 * 3600,
            'date'            => $tomorrow->format('Y-m-d'),
            'type'            => EventTypeEnum::INDIVIDUAL->value,
            'description'     => 'Busy',
        ]);
        $event->users()->attach($this->user->getKey());

        $payload = [
            'title'       => 'Overlap attempt',
            'fromDate'    => $tomorrow->format('Y-m-d'),
            'fromTime'    => '11:00', // inside busy block
            'toDate'      => $tomorrow->format('Y-m-d'),
            'toTime'      => '12:00',
            'type'        => EventTypeEnum::INDIVIDUAL->value,
            'description' => 'Should fail due to overlap',
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
            'type'              => EventTypeEnum::INDIVIDUAL->value,
            'description'       => 'Test description',
            'timezone'          => 'Europe/Kyiv',
        ];

        $response = $this->withoutMiddleware()
            ->post(route('pages.calendar.store'), $eventData);
        $response->assertStatus(Response::HTTP_FORBIDDEN);
    });
});
