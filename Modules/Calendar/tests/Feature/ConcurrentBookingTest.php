<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;
use Modules\Calendar\Enums\CalendarEventColoursEnum;
use Modules\Calendar\Enums\CalendarEventRoleEnum;
use Modules\Calendar\Enums\CalendarEventStatusEnum;
use Modules\Calendar\Enums\CalendarEventTypeEnum;
use Modules\Calendar\Models\CalendarEvent;
use Modules\MentorProgram\Models\MentorProgram;
use Spatie\Permission\Models\Role;

describe('Concurrent Booking (Feature)', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        $this->mentor = User::factory()->create();
        $this->mentor->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        $this->mentor->profile->timezone = 'Europe/Kyiv';
        $this->mentor->profile->save();

        $this->mentee1 = User::factory()->create();
        $this->mentee2 = User::factory()->create();

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
        ]);

        Date::setTestNow(Date::create(2026, 2, 10, 10, 0, 0, 'UTC'));
    });

    describe('Overlap prevention', function (): void {
        it('prevents confirming second PENDING event when first is CONFIRMED for same slot', function (): void {
            $eventDateTime = Date::tomorrow()->setTime(14, 0, 0);

            // Create first event - PENDING
            $event1 = CalendarEvent::factory()->create([
                'title'             => 'First Event',
                'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION->value,
                'start_date_time'   => $eventDateTime,
                'end_date_time'     => (clone $eventDateTime)->addHour(),
                'date'              => $eventDateTime->format('Y-m-d'),
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ]);

            $event1->calendarEventUsers()->attach($this->mentor->getKey(), [
                'role'   => CalendarEventRoleEnum::HOST->value,
                'colour' => CalendarEventColoursEnum::BLUE->value,
            ]);
            $event1->calendarEventUsers()->attach($this->mentee1->getKey(), [
                'role'   => CalendarEventRoleEnum::MENTI->value,
                'colour' => CalendarEventColoursEnum::GREEN->value,
            ]);

            // Create second event - PENDING at same time
            $event2 = CalendarEvent::factory()->create([
                'title'             => 'Second Event',
                'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION->value,
                'start_date_time'   => $eventDateTime,
                'end_date_time'     => (clone $eventDateTime)->addHour(),
                'date'              => $eventDateTime->format('Y-m-d'),
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ]);

            $event2->calendarEventUsers()->attach($this->mentor->getKey(), [
                'role'   => CalendarEventRoleEnum::HOST->value,
                'colour' => CalendarEventColoursEnum::BLUE->value,
            ]);
            $event2->calendarEventUsers()->attach($this->mentee2->getKey(), [
                'role'   => CalendarEventRoleEnum::MENTI->value,
                'colour' => CalendarEventColoursEnum::GREEN->value,
            ]);

            // Confirm first event
            $this->actingAs($this->mentor);
            auth()->login($this->mentor);
            $response1 = $this->withSession(['_token' => 'test-token'])
                ->patch(route('calendar.confirm.booking',
                    [
                        $this->mentorProgram->getKey(),
                        $event1->getKey(),
                        '_token' => csrf_token(),
                    ]));

            $response1->assertRedirect();

            expect($event1->fresh()->status)->toBe(CalendarEventStatusEnum::CONFIRMED);

            // Try to confirm second event - should fail due to overlap
            $response2 = $this->withSession(['_token' => 'test-token'])
                ->patch(route('calendar.confirm.booking', [
                    $this->mentorProgram->getKey(),
                    $event2->getKey(),
                    '_token' => csrf_token(),
                ]));

            $response2->assertRedirect(route('pages.calendar.pending'));
            $response2->assertSessionHas('error');

            // Second event should still be PENDING
            expect($event2->fresh()->status)->toBe(CalendarEventStatusEnum::CANCELLED);
        });

        it('allows booking adjacent slots without overlap', function (): void {
            $eventDateTime1 = Date::tomorrow()->setTime(14, 0, 0);
            $eventDateTime2 = Date::tomorrow()->setTime(15, 0, 0); // Starts when first ends

            // Create first event
            $event1 = CalendarEvent::factory()->create([
                'title'             => 'First Event',
                'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION->value,
                'start_date_time'   => $eventDateTime1,
                'end_date_time'     => (clone $eventDateTime1)->addHour(),
                'date'              => $eventDateTime1->format('Y-m-d'),
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ]);

            $event1->calendarEventUsers()->attach($this->mentor->getKey(), [
                'role'   => CalendarEventRoleEnum::HOST->value,
                'colour' => CalendarEventColoursEnum::BLUE->value,
            ]);
            $event1->calendarEventUsers()->attach($this->mentee1->getKey(), [
                'role'   => CalendarEventRoleEnum::MENTI->value,
                'colour' => CalendarEventColoursEnum::GREEN->value,
            ]);

            // Create second event - adjacent (not overlapping)
            $event2 = CalendarEvent::factory()->create([
                'title'             => 'Second Event',
                'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION->value,
                'start_date_time'   => $eventDateTime2,
                'end_date_time'     => (clone $eventDateTime2)->addHour(),
                'date'              => $eventDateTime2->format('Y-m-d'),
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ]);

            $event2->calendarEventUsers()->attach($this->mentor->getKey(), [
                'role'   => CalendarEventRoleEnum::HOST->value,
                'colour' => CalendarEventColoursEnum::BLUE->value,
            ]);
            $event2->calendarEventUsers()->attach($this->mentee2->getKey(), [
                'role'   => CalendarEventRoleEnum::MENTI->value,
                'colour' => CalendarEventColoursEnum::GREEN->value,
            ]);

            // Confirm first event
            $this->actingAs($this->mentor);
            auth()->login($this->mentor);
            $this->withSession(['_token' => 'test-token'])
                ->patch(route('calendar.confirm.booking',
                    [
                        $this->mentorProgram->getKey(),
                        $event1->getKey(),
                        '_token' => csrf_token(),
                    ]));

            expect($event1->fresh()->status)->toBe(CalendarEventStatusEnum::CONFIRMED);

            // Confirm second event - should succeed because no overlap
            $response2 = $this->withSession(['_token' => 'test-token'])
                ->patch(route('calendar.confirm.booking', [
                    $this->mentorProgram->getKey(),
                    $event2->getKey(),
                    '_token' => csrf_token(),
                ]));

            $response2->assertRedirect(route('pages.calendar.pending'));

            expect($event2->fresh()->status)->toBe(CalendarEventStatusEnum::CONFIRMED);
        });

        it('blocks partial overlap scenarios', function (): void {
            $eventDateTime1 = Date::tomorrow()->setTime(14, 0, 0);
            // Second event starts 30 min after first starts (partial overlap)
            $eventDateTime2 = Date::tomorrow()->setTime(14, 30, 0);

            // Create first event (14:00 - 15:00)
            $event1 = CalendarEvent::factory()->create([
                'title'             => 'First Event',
                'status'            => CalendarEventStatusEnum::CONFIRMED->value,
                'start_date_time'   => $eventDateTime1,
                'end_date_time'     => (clone $eventDateTime1)->addHour(),
                'date'              => $eventDateTime1->format('Y-m-d'),
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ]);

            $event1->calendarEventUsers()->attach($this->mentor->getKey(), [
                'role'   => CalendarEventRoleEnum::HOST->value,
                'colour' => CalendarEventColoursEnum::BLUE->value,
            ]);

            // Create second event (14:30 - 15:30) - partial overlap
            $event2 = CalendarEvent::factory()->create([
                'title'             => 'Second Event',
                'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION->value,
                'start_date_time'   => $eventDateTime2,
                'end_date_time'     => (clone $eventDateTime2)->addHour(),
                'date'              => $eventDateTime2->format('Y-m-d'),
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ]);

            $event2->calendarEventUsers()->attach($this->mentor->getKey(), [
                'role'   => CalendarEventRoleEnum::HOST->value,
                'colour' => CalendarEventColoursEnum::BLUE->value,
            ]);
            $event2->calendarEventUsers()->attach($this->mentee2->getKey(), [
                'role'   => CalendarEventRoleEnum::MENTI->value,
                'colour' => CalendarEventColoursEnum::GREEN->value,
            ]);

            // Try to confirm second event - should fail due to partial overlap
            $this->actingAs($this->mentor);
            auth()->login($this->mentor);
            $response = $this->withSession(['_token' => 'test-token'])
                ->patch(route('calendar.confirm.booking', [
                    $this->mentorProgram->getKey(),
                    $event2->getKey(),
                    '_token' => csrf_token(),
                ]));

            $response->assertRedirect(route('pages.calendar.pending'));
            $response->assertSessionHas('error');

            // Second event should still be PENDING
            expect($event2->fresh()->status)->toBe(CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION);
        });

        it('blocks event completely inside another confirmed event', function (): void {
            $eventDateTime1 = Date::tomorrow()->setTime(13, 0, 0);
            // Second event is completely inside first
            $eventDateTime2 = Date::tomorrow()->setTime(14, 0, 0);

            // Create first event (13:00 - 16:00) - 3 hour event
            $event1 = CalendarEvent::factory()->create([
                'title'             => 'Long Event',
                'status'            => CalendarEventStatusEnum::CONFIRMED->value,
                'start_date_time'   => $eventDateTime1,
                'end_date_time'     => (clone $eventDateTime1)->addHours(3),
                'date'              => $eventDateTime1->format('Y-m-d'),
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ]);

            $event1->calendarEventUsers()->attach($this->mentor->getKey(), [
                'role'   => CalendarEventRoleEnum::HOST->value,
                'colour' => CalendarEventColoursEnum::BLUE->value,
            ]);

            // Create second event (14:00 - 15:00) - completely inside first
            $event2 = CalendarEvent::factory()->create([
                'title'             => 'Nested Event',
                'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION->value,
                'start_date_time'   => $eventDateTime2,
                'end_date_time'     => (clone $eventDateTime2)->addHour(),
                'date'              => $eventDateTime2->format('Y-m-d'),
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ]);

            $event2->calendarEventUsers()->attach($this->mentor->getKey(), [
                'role'   => CalendarEventRoleEnum::HOST->value,
                'colour' => CalendarEventColoursEnum::BLUE->value,
            ]);
            $event2->calendarEventUsers()->attach($this->mentee2->getKey(), [
                'role'   => CalendarEventRoleEnum::MENTI->value,
                'colour' => CalendarEventColoursEnum::GREEN->value,
            ]);

            // Try to confirm second event - should fail
            $this->actingAs($this->mentor);
            auth()->login($this->mentor);
            $response = $this->withSession(['_token' => 'test-token'])
                ->patch(route('calendar.confirm.booking', [
                    $this->mentorProgram->getKey(),
                    $event2->getKey(),
                    '_token' => csrf_token(),
                ]));

            $response->assertRedirect(route('pages.calendar.pending'));
            $response->assertSessionHas('error');

            expect($event2->fresh()->status)->toBe(CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION);
        });
    });
});
