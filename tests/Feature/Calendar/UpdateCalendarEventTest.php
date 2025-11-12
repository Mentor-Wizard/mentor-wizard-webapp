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
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

describe('Calendar CalendarEvent Edit Page', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        auth()->login($this->user);
        $this->nonMentorUser = User::factory()->create();
        $this->event = CalendarEvent::factory()->create([
            'title'             => 'Default event',
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => Date::tomorrow()->format('Y-m-d').' 12:00:00',
            'end_date_time'     => Date::tomorrow()->format('Y-m-d').' 13:00:00',
            'date'              => Date::tomorrow()->format('Y-m-d'),
            'duration'          => 3600,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'description'       => 'Test description',
        ]);
        $this->event->calendarEventUsers()->attach($this->user->getKey(),
            ['role' => CalendarEventRoleEnum::HOST, 'colour' => CalendarEventColoursEnum::BLUE->value]);
    });

    it('updates event successfully', function (): void {
        actingAs($this->user);

        $updateEventData = [
            'title'             => 'Default event',
            'fromDate'          => Date::today()->addDays(6)->format('Y-m-d'),
            'fromTime'          => '14:00',
            'toDate'            => Date::today()->addDays(6)->format('Y-m-d'),
            'toTime'            => '16:00',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'description'       => 'Test description',
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'timezone'          => 'Europe/Kyiv',
        ];

        $response = $this->withSession(['_token' => 'test-token'])
            ->patch(route('pages.calendar.edit', $this->event->getKey()), [
                ...$updateEventData,
                '_token' => 'test-token',
            ]);

        $response->assertRedirect(route('pages.calendar.index'));
        // Times are stored in UTC, so 14:00 Europe/Kyiv = 12:00 UTC (2 hour offset)
        $this->assertDatabaseHas('calendar_events', [
            'title'             => 'Default event',
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => Date::today()->addDays(6)->format('Y-m-d').' 12:00:00',
            'end_date_time'     => Date::today()->addDays(6)->format('Y-m-d').' 14:00:00',
            'date'              => Date::today()->addDays(6)->format('Y-m-d'),
            'duration'          => 7200,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'description'       => 'Test description',
            'mentor_program_id' => null,
        ]);

        $this->assertDatabaseHas('calendar_event_user', [
            'calendar_event_id' => $this->event->getKey(),
            'user_id'           => $this->user->getKey(),
            'role'              => CalendarEventRoleEnum::HOST,
        ]);
    });

    it('validates input when storing event', function (): void {
        actingAs($this->user);

        $invalidData = [
            'title'             => Str::random(256),
            'fromDate'          => Date::today()->addDays(2)->format('Y-m-d'),
            'fromTime'          => '09:00',
            'toDate'            => Date::today()->addDays(2)->format('Y-m-d'),
            'toTime'            => '10:00',
            'type'              => 'invalid_type',
            'description'       => Str::random(2001),
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'timezone'          => 'Europe/Kyiv',
        ];

        $response = $this->withSession(['_token' => 'test-token'])
            ->patch(route('pages.calendar.edit', $this->event->getKey()), [
                ...$invalidData,
                '_token' => 'test-token',
            ]);
        $response->assertSessionHasErrors(['description', 'title', 'type']);
    });

    it('throws 403 when a non-mentor user tries to update an event', function (): void {
        actingAs($this->nonMentorUser);
        auth()->login($this->nonMentorUser);

        $eventData = [
            'title'             => 'Default event',
            'fromDate'          => Date::today()->addDays(2)->format('Y-m-d'),
            'fromTime'          => '09:00',
            'toDate'            => Date::today()->addDays(2)->format('Y-m-d'),
            'toTime'            => '10:00',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'description'       => 'Test description',
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'timezone'          => 'Europe/Kyiv',
        ];
        $response = $this->withSession(['_token' => 'test-token'])
            ->patch(route('pages.calendar.edit', $this->event->getKey()), [
                ...$eventData,
                '_token' => 'test-token',
            ]);
        $response->assertForbidden();
    });
});
