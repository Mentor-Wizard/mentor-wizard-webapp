<?php

declare(strict_types=1);

use App\Enums\EventRoleEnum;
use App\Enums\EventStatusEnum;
use App\Enums\EventTypeEnum;
use App\Enums\RoleEnum;
use App\Models\Event as EventModel;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

use function Pest\Laravel\actingAs;

describe('Calendar Event Edit Page', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        auth()->login($this->user);
        $this->nonMentorUser = User::factory()->create();
        $this->event = EventModel::factory()->create([
            'title'             => 'Default event',
            'status'            => EventStatusEnum::CONFIRMED,
            'start_date_time'   => Carbon::tomorrow()->format('Y-m-d').' 12:00:00',
            'end_date_time'     => Carbon::tomorrow()->format('Y-m-d').' 13:00:00',
            'date'              => Carbon::tomorrow()->format('Y-m-d'),
            'duration'          => 3600,
            'type'              => EventTypeEnum::INDIVIDUAL->value,
            'description'       => 'Test description',
        ]);
        $this->event->users()->attach($this->user->getKey(), ['role' => EventRoleEnum::HOST]);
    });

    it('updates event successfully', function (): void {
        actingAs($this->user);

        $updateEventData = [
            'id'                => $this->event->getKey(),
            'title'             => 'Default event',
            'fromDate'          => Carbon::today()->addDays(6)->format('Y-m-d'),
            'fromTime'          => '14:00',
            'toDate'            => Carbon::today()->addDays(6)->format('Y-m-d'),
            'toTime'            => '16:00',
            'type'              => EventTypeEnum::INDIVIDUAL->value,
            'description'       => 'Test description',
            'timezone'          => 'Europe/Kyiv',
        ];

        $response = $this->withoutMiddleware()
            ->patch(route('pages.calendar.edit', $this->event->getKey()), $updateEventData);

        $response->assertRedirect(route('pages.calendar'));
        $this->assertDatabaseHas('events', [
            'title'             => 'Default event',
            'status'            => EventStatusEnum::CONFIRMED,
            'start_date_time'   => Carbon::today()->addDays(6)->format('Y-m-d').' 14:00:00',
            'end_date_time'     => Carbon::today()->addDays(6)->format('Y-m-d').' 16:00:00',
            'date'              => Carbon::today()->addDays(6)->format('Y-m-d'),
            'duration'          => 7200,
            'type'              => EventTypeEnum::INDIVIDUAL->value,
            'description'       => 'Test description',
            'mentor_program_id' => null,
        ]);

        $this->assertDatabaseHas('event_user', [
            'event_id'          => $this->event->getKey(),
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

        $response = $this->withoutMiddleware()->patch(route('pages.calendar.edit', $this->event->getKey()), $invalidData);
        $response->assertSessionHasErrors(['fromDate', 'description', 'title', 'type']);

        $invalidData = [
            'title'             => Str::random(256),
            'fromDate'          => '2024-08-15',
            'fromTime'          => null,
            'toDate'            => null,
            'toTime'            => null,
            'type'              => 'individual',
            'description'       => Str::random(2001),
            'timezone'          => 'Europe/Kyiv',
        ];

        $response = $this->withoutMiddleware()->patch(route('pages.calendar.edit', $this->event->getKey()), $invalidData);
        $response->assertSessionHasErrors(['fromDate', 'description', 'title', 'type']);
    });

    it('throws 403 when a non-mentor user tries to create an event', function (): void {
        actingAs($this->nonMentorUser);
        auth()->login($this->nonMentorUser);
        $eventData = [
            'title'             => 'Default event',
            'fromDate'          => Carbon::today()->addDays(2)->format('Y-m-d'),
            'fromTime'          => '09:00',
            'toDate'            => Carbon::today()->addDays(2)->format('Y-m-d'),
            'toTime'            => '10:00',
            'type'              => EventTypeEnum::INDIVIDUAL->value,
            'description'       => 'Test description',
            'timezone'          => 'Europe/Kyiv',
        ];

        $response = $this->withoutMiddleware()
            ->patch(route('pages.calendar.edit', $this->event->getKey()), $eventData);
        $response->assertStatus(Response::HTTP_FORBIDDEN);
    });
});
