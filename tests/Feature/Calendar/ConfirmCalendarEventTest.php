<?php

declare(strict_types=1);

use App\Enums\CalendarEventColoursEnum;
use App\Enums\CalendarEventRoleEnum;
use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Enums\RoleEnum;
use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;
use Spatie\Permission\Models\Role;

describe('ConfirmCalendarEvent (Feature)', function (): void {
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

    it('host confirms and event becomes confirmed with success message', function (): void {
        $this->actingAs($this->host);
        auth()->login($this->host);
        $response = $this->withSession(['_token' => 'test-token'])
            ->patch(route('calendar.confirm.booking',
                [$this->event->getKey(), '_token' => csrf_token()]));

        $response->assertRedirect(route('pages.calendar.pending'));
        $response->assertSessionHas('success', 'Event was successfully confirmed.');

        expect($this->event->fresh()->status)->toBe(CalendarEventStatusEnum::CONFIRMED->value);
    });

    it('mentee confirms and sees waiting for cohost message while status stays pending', function (): void {
        $this->actingAs($this->mentee);

        $response = $this
            ->withSession(['_token' => 'test-token'])
            ->patch(route('calendar.confirm.booking',
                [$this->event->getKey(), '_token' => csrf_token()]));

        $response->assertRedirect(route('pages.calendar.pending'));
        $response->assertSessionHas('success', 'Event is confirmed on your side, but waiting for confirmation from CO-HOST');

        expect($this->event->fresh()->status)->toBe(CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION->value);
    });
});
