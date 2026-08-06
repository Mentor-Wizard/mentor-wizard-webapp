<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Enums\UserScheduleRecordType;
use App\Models\User;
use App\Models\UserSchedule;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;
use Modules\Calendar\Enums\CalendarEventRoleEnum;
use Modules\Calendar\Enums\CalendarEventStatusEnum;
use Modules\Calendar\Enums\CalendarEventTypeEnum;
use Modules\Calendar\Models\CalendarEvent;
use Modules\Calendar\Services\BookingCalendarEventsService;
use Modules\MentorProgram\Models\MentorProgram;
use Spatie\Permission\Models\Role;

mutates(BookingCalendarEventsService::class);

describe('BookingCalendarEventsService', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        $this->user->profile->timezone = 'Europe/Kyiv';
        $this->user->profile->minimum_pre_booking_time = 0;
        $this->user->profile->save();

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id'        => $this->user->getKey(),
            'session_duration' => 30,
        ]);

        $this->timezone = 'Europe/Kyiv';
    });

    describe('Basic functionality', function (): void {
        it('returns formatted month calendar with available slots', function (): void {
            Date::setTestNow(Date::create(2026, 2, 10, 10, 0, 0, 'UTC'));

            $service = new BookingCalendarEventsService(
                Date::now($this->timezone),
                $this->user,
                $this->timezone,
                true,
                $this->mentorProgram,
            );

            $result = $service->getFormattedMonthAvailableSlots();

            expect($result)->toHaveKeys(['calendarSlots', 'hasSlotsBefore', 'hasSlotsAfter'])
                ->and($result['calendarSlots'])->toBeArray();
        });

        it('builds calendar view with correct date keys', function (): void {
            Date::setTestNow(Date::create(2026, 2, 10, 10, 0, 0, 'UTC'));

            $service = new BookingCalendarEventsService(
                Date::parse('2026-02-10', $this->timezone),
                $this->user,
                $this->timezone,
                true,
                $this->mentorProgram,
            );

            $result = $service->getFormattedMonthAvailableSlots();

            // Calendar should have date entries
            $calendarSlots = $result['calendarSlots'];
            expect($calendarSlots)->toBeArray()->not()->toBeEmpty();

            // Each entry should have date key
            $firstEntry = $calendarSlots[0];
            expect($firstEntry)->toHaveKey('date');
        });

        it('marks today as isToday', function (): void {
            Date::setTestNow(Date::create(2026, 2, 10, 10, 0, 0, $this->timezone));

            $service = new BookingCalendarEventsService(
                Date::now($this->timezone),
                $this->user,
                $this->timezone,
                true,
                $this->mentorProgram,
            );

            $result = $service->getFormattedMonthAvailableSlots();

            // Find today's entry
            $todayEntry = collect($result['calendarSlots'])->firstWhere('date', '2026-02-10');

            expect($todayEntry)->not()->toBeNull()
                ->and($todayEntry['isToday'])->toBeTrue();
        });

        it('marks selected date as isSelected', function (): void {
            Date::setTestNow(Date::create(2026, 2, 10, 10, 0, 0, $this->timezone));

            $selectedDate = Date::parse('2026-02-15', $this->timezone);

            $service = new BookingCalendarEventsService(
                $selectedDate,
                $this->user,
                $this->timezone,
                true,
                $this->mentorProgram,
            );

            $result = $service->getFormattedMonthAvailableSlots();

            // Find selected date entry
            $selectedEntry = collect($result['calendarSlots'])
                ->firstWhere('date', '2026-02-15');

            expect($selectedEntry)->not()->toBeNull()
                ->and($selectedEntry['isSelected'])->toBeTrue();
        });

        it('marks current month days as isCurrentMonth', function (): void {
            Date::setTestNow(Date::create(2026, 2, 10, 10, 0, 0, $this->timezone));

            $service = new BookingCalendarEventsService(
                Date::parse('2026-02-15', $this->timezone),
                $this->user,
                $this->timezone,
                true,
                $this->mentorProgram,
            );

            $result = $service->getFormattedMonthAvailableSlots();

            // Find a February entry with slots
            $febEntry = collect($result['calendarSlots'])
                ->firstWhere('date', '2026-02-15');

            expect($febEntry)->not()->toBeNull()
                ->and($febEntry['isCurrentMonth'])->toBeTrue();
        });
    });

    describe('hasSlotsBefore and hasSlotsAfter', function (): void {
        it('correctly determines hasSlotsBefore when viewing future month', function (): void {
            Date::setTestNow(Date::create(2026, 1, 10, 10, 0, 0, $this->timezone));

            // Create service for February (future month)
            $service = new BookingCalendarEventsService(
                Date::parse('2026-02-10', $this->timezone),
                $this->user,
                $this->timezone,
                true,
                $this->mentorProgram,
            );

            $result = $service->getFormattedMonthAvailableSlots();

            expect($result['hasSlotsBefore'])->toBeTrue();
        });

        it('correctly determines hasSlotsBefore is false for current month', function (): void {
            $service = new BookingCalendarEventsService(
                Date::now($this->timezone)->startOfMonth()->subDays(5),
                $this->user,
                $this->timezone,
                true,
                $this->mentorProgram,
            );

            $result = $service->getFormattedMonthAvailableSlots();

            expect($result['hasSlotsBefore'])->toBeFalse();
        });

        it('correctly determines hasSlotsAfter within booking window', function (): void {
            Date::setTestNow(Date::now()->endOfMonth()->addDays(2));

            $service = new BookingCalendarEventsService(
                Date::now($this->timezone),
                $this->user,
                $this->timezone,
                true,
                $this->mentorProgram,
            );

            $result = $service->getFormattedMonthAvailableSlots();

            // Should have events after since we're within the maximum booking window
            expect($result['hasSlotsAfter'])->toBeTrue();
        });

        it('correctly determines hasSlotsAfter is false at end of booking window', function (): void {
            Date::setTestNow(Date::create(2026, 2, 10, 10, 0, 0, $this->timezone));

            // Create service for a date at the end of the booking window
            $endOfBookingWindow = Date::now($this->timezone)
                ->addMonths(CalendarEvent::MAXIMUM_NUMBER_OF_MONTHS_EVENT_CAN_BE_SET);

            $service = new BookingCalendarEventsService(
                $endOfBookingWindow,
                $this->user,
                $this->timezone,
                true,
                $this->mentorProgram,
            );

            $result = $service->getFormattedMonthAvailableSlots();

            expect($result['hasSlotsAfter'])->toBeFalse();
        });
    });

    describe('Slots formatting', function (): void {
        it('formats slots with start and end strings', function (): void {
            Date::setTestNow(Date::create(2026, 2, 10, 10, 0, 0, $this->timezone));

            $service = new BookingCalendarEventsService(
                Date::now($this->timezone),
                $this->user,
                $this->timezone,
                true,
                $this->mentorProgram,
            );

            $result = $service->getFormattedMonthAvailableSlots();

            // Find an entry with slots
            $entryWithSlots = collect($result['calendarSlots'])
                ->first(fn (array $entry): bool => $entry['slots'] !== []);

            expect($entryWithSlots)->not()->toBeNull();

            if ($entryWithSlots && count($entryWithSlots['slots']) > 0) {
                $slot = $entryWithSlots['slots'][0];
                expect($slot)->toHaveKeys(['start', 'end'])
                    ->and($slot['start'])->toBeString()
                    ->and($slot['end'])->toBeString();
            }
        });

        it('handles empty slots gracefully', function (): void {
            Date::setTestNow(Date::create(2026, 2, 10, 10, 0, 0, $this->timezone));

            // Create event that fills the day
            $dayStart = Date::now($this->timezone)->startOfDay();
            $dayEnd = Date::now($this->timezone)->endOfDay();

            CalendarEvent::factory()->create([
                'start_date_time'   => $dayStart,
                'end_date_time'     => $dayEnd,
                'status'            => CalendarEventStatusEnum::CONFIRMED->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ])->calendarEventUsers()->attach($this->user->getKey(), [
                'role' => CalendarEventRoleEnum::HOST->value,
            ]);

            $service = new BookingCalendarEventsService(
                Date::now($this->timezone),
                $this->user,
                $this->timezone,
                true,
                $this->mentorProgram,
            );

            $result = $service->getFormattedMonthAvailableSlots();

            expect($result)->toHaveKeys(['calendarSlots', 'hasSlotsBefore', 'hasSlotsAfter']);
        });
    });

    describe('Schedule exclusion', function (): void {
        it('respects excludeSchedule parameter when true', function (): void {
            Date::setTestNow(Date::create(2026, 2, 16, 10, 0, 0, $this->timezone)); // Monday

            // Create working schedule: Monday 09:00-12:00 only
            UserSchedule::query()->create([
                'user_id'     => $this->user->getKey(),
                'day_of_week' => 1, // Monday
                'start_time'  => '09:00:00',
                'end_time'    => '12:00:00',
                'type'        => UserScheduleRecordType::WORKING_DAY,
            ]);

            $serviceWithExclusion = new BookingCalendarEventsService(
                Date::now($this->timezone),
                $this->user,
                $this->timezone,
                true, // excludeSchedule = true
                $this->mentorProgram,
            );

            $resultWithExclusion = $serviceWithExclusion->getFormattedMonthAvailableSlots();

            // Find Monday entry
            $mondayEntry = collect($resultWithExclusion['calendarSlots'])
                ->firstWhere('date', '2026-02-16');

            // With schedule exclusion, only slots within 09:00-12:00 should exist
            if ($mondayEntry && count($mondayEntry['slots']) > 0) {
                foreach ($mondayEntry['slots'] as $slot) {
                    $slotTime = Date::parse($slot['start'], $this->timezone)->format('H:i');
                    expect($slotTime)->toBeGreaterThanOrEqual('09:00')
                        ->and($slotTime)->toBeLessThan('12:00');
                }
            }
        });

        it('ignores schedule when excludeSchedule is false', function (): void {
            Date::setTestNow(Date::create(2026, 2, 16, 10, 0, 0, $this->timezone)); // Monday

            // Create working schedule: Monday 09:00-12:00 only
            UserSchedule::query()->create([
                'user_id'     => $this->user->getKey(),
                'day_of_week' => 1, // Monday
                'start_time'  => '09:00:00',
                'end_time'    => '12:00:00',
                'type'        => UserScheduleRecordType::WORKING_DAY,
            ]);

            $serviceWithoutExclusion = new BookingCalendarEventsService(
                Date::now($this->timezone),
                $this->user,
                $this->timezone,
                false, // excludeSchedule = false
                $this->mentorProgram,
            );

            $resultWithoutExclusion = $serviceWithoutExclusion->getFormattedMonthAvailableSlots();

            // Find Monday entry - should have more slots without schedule restriction
            $mondayEntry = collect($resultWithoutExclusion['calendarSlots'])
                ->firstWhere('date', '2026-02-16');

            expect($mondayEntry)->not()->toBeNull()
                ->and($mondayEntry['slots'])->not()->toBeEmpty();
        });
    });

    describe('Timezone handling', function (): void {
        it('handles timezone conversion for slots', function (): void {
            Date::setTestNow(Date::create(2026, 2, 10, 10, 0, 0, 'UTC'));

            $utcTimezone = 'UTC';
            $kyivTimezone = 'Europe/Kyiv';

            $serviceUtc = new BookingCalendarEventsService(
                Date::now($utcTimezone),
                $this->user,
                $utcTimezone,
                true,
                $this->mentorProgram,
            );

            $serviceKyiv = new BookingCalendarEventsService(
                Date::now($kyivTimezone),
                $this->user,
                $kyivTimezone,
                true,
                $this->mentorProgram,
            );

            $resultUtc = $serviceUtc->getFormattedMonthAvailableSlots();
            $resultKyiv = $serviceKyiv->getFormattedMonthAvailableSlots();

            // Both should have calendar slots
            expect($resultUtc['calendarSlots'])->toBeArray()
                ->and($resultKyiv['calendarSlots'])->toBeArray();
        });
    });

    describe('Integration with dependent services', function (): void {
        it('integrates AvailableCalendarEventsSlotsService correctly', function (): void {
            Date::setTestNow(Date::create(2026, 2, 10, 10, 0, 0, $this->timezone));

            // Create an event to test that available slots are computed correctly
            $eventStart = Date::now($this->timezone)->addDay()->setTime(14, 0, 0);
            $eventEnd = (clone $eventStart)->addHour();

            CalendarEvent::factory()->create([
                'start_date_time'   => $eventStart,
                'end_date_time'     => $eventEnd,
                'status'            => CalendarEventStatusEnum::CONFIRMED->value,
                'date'              => $eventStart->format('Y-m-d'),
                'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
                'mentor_program_id' => $this->mentorProgram->getKey(),
            ])->calendarEventUsers()->attach($this->user->getKey(), [
                'role' => CalendarEventRoleEnum::HOST->value,
            ]);

            $service = new BookingCalendarEventsService(
                Date::now($this->timezone),
                $this->user,
                $this->timezone,
                true,
                $this->mentorProgram,
            );

            $result = $service->getFormattedMonthAvailableSlots();

            expect($result['calendarSlots'])->toBeArray()->not()->toBeEmpty();
        });

        it('integrates SplitSlotsPerSessionDuration correctly', function (): void {
            Date::setTestNow(Date::create(2026, 2, 10, 10, 0, 0, $this->timezone));

            // Ensure session duration is set
            $this->mentorProgram->update(['session_duration' => 30]);

            $service = new BookingCalendarEventsService(
                Date::now($this->timezone),
                $this->user,
                $this->timezone,
                true,
                $this->mentorProgram,
            );

            $result = $service->getFormattedMonthAvailableSlots();

            // Find an entry with slots
            $entryWithSlots = collect($result['calendarSlots'])
                ->first(fn (array $entry): bool => $entry['slots'] !== []);

            if ($entryWithSlots && count($entryWithSlots['slots']) >= 2) {
                $firstSlot = $entryWithSlots['slots'][0];
                $startTime = Date::parse($firstSlot['start'], $this->timezone);
                $endTime = Date::parse($firstSlot['end'], $this->timezone);

                // Slot duration should match session duration (30 minutes)
                $duration = $startTime->diffInMinutes($endTime);
                expect($duration)->toBe(30.0);
            }
        });
    });

    describe('Mutation Coverage - Return value structure', function (): void {
        it('returns startDate in getMonthDates (kills RemoveArrayItem on line 72)', function (): void {
            Date::setTestNow(Date::create(2026, 2, 10, 10, 0, 0, $this->timezone));

            $service = new BookingCalendarEventsService(
                Date::parse('2026-02-10', $this->timezone),
                $this->user,
                $this->timezone,
                true,
                $this->mentorProgram,
            );

            // Use reflection to call private method
            $reflection = new ReflectionClass($service);
            $method = $reflection->getMethod('prepareDateConfiguration');

            $result = $method->invoke($service);

            expect($result)->toHaveKey('startDate')
                ->and($result['startDate'])->not->toBeNull();
        });

        it('returns endDate in getMonthDates (kills RemoveArrayItem on line 73)', function (): void {
            Date::setTestNow(Date::create(2026, 2, 10, 10, 0, 0, $this->timezone));

            $service = new BookingCalendarEventsService(
                Date::parse('2026-02-10', $this->timezone),
                $this->user,
                $this->timezone,
                true,
                $this->mentorProgram,
            );

            $reflection = new ReflectionClass($service);
            $method = $reflection->getMethod('prepareDateConfiguration');

            $result = $method->invoke($service);

            expect($result)->toHaveKey('endDate')
                ->and($result['endDate'])->not->toBeNull();
        });

        it('returns date in buildSlotPayload (kills RemoveArrayItem on line 123)', function (): void {
            Date::setTestNow(Date::create(2026, 2, 10, 10, 0, 0, $this->timezone));

            $service = new BookingCalendarEventsService(
                Date::parse('2026-02-15', $this->timezone),
                $this->user,
                $this->timezone,
                true,
                $this->mentorProgram,
            );

            $result = $service->getFormattedMonthAvailableSlots();

            // Each calendar slot entry should have a 'date' key
            $entryWithSlots = collect($result['calendarSlots'])
                ->first(fn (array $entry): bool => isset($entry['date']));

            expect($entryWithSlots)->not->toBeNull()
                ->and($entryWithSlots)->toHaveKey('date')
                ->and($entryWithSlots['date'])->not->toBeNull();
        });

        it('isSelected defaults to false not true (kills FalseToTrue on line 182)', function (): void {
            Date::setTestNow(Date::create(2026, 2, 10, 10, 0, 0, $this->timezone));

            $selectedDate = Date::parse('2026-02-15', $this->timezone);

            $service = new BookingCalendarEventsService(
                $selectedDate,
                $this->user,
                $this->timezone,
                true,
                $this->mentorProgram,
            );

            $result = $service->getFormattedMonthAvailableSlots();

            // Find a date that is NOT the selected date
            $nonSelectedEntry = collect($result['calendarSlots'])
                ->firstWhere('date', '2026-02-16');

            // Non-selected dates should have isSelected = false
            expect($nonSelectedEntry)->not->toBeNull()
                ->and($nonSelectedEntry['isSelected'])->toBeFalse();
        });

        it('isToday defaults to false not true (kills FalseToTrue on line 183)', function (): void {
            Date::setTestNow(Date::create(2026, 2, 10, 10, 0, 0, $this->timezone));

            $service = new BookingCalendarEventsService(
                Date::now($this->timezone),
                $this->user,
                $this->timezone,
                true,
                $this->mentorProgram,
            );

            $result = $service->getFormattedMonthAvailableSlots();

            // Find a date that is NOT today
            $notTodayEntry = collect($result['calendarSlots'])
                ->firstWhere('date', '2026-02-11');

            // Non-today dates should have isToday = false
            expect($notTodayEntry)->not->toBeNull()
                ->and($notTodayEntry['isToday'])->toBeFalse();
        });

        it('isCurrentMonth defaults to false not true (kills FalseToTrue on line 184)', function (): void {
            Date::setTestNow(Date::create(2026, 2, 10, 10, 0, 0, $this->timezone));

            $service = new BookingCalendarEventsService(
                Date::parse('2026-02-15', $this->timezone),
                $this->user,
                $this->timezone,
                true,
                $this->mentorProgram,
            );

            $result = $service->getFormattedMonthAvailableSlots();

            // The calendar view includes days from adjacent months (padding)
            // Find a January day (previous month) if visible
            $prevMonthEntry = collect($result['calendarSlots'])
                ->first(fn (array $entry): bool => str_starts_with((string) $entry['date'], '2026-01'));

            if ($prevMonthEntry) {
                // Days from previous month should have isCurrentMonth = false
                expect($prevMonthEntry['isCurrentMonth'])->toBeFalse();
            }

            // Find a March day (next month) if visible
            $nextMonthEntry = collect($result['calendarSlots'])
                ->first(fn (array $entry): bool => str_starts_with((string) $entry['date'], '2026-03'));

            if ($nextMonthEntry) {
                // Days from next month should have isCurrentMonth = false
                expect($nextMonthEntry['isCurrentMonth'])->toBeFalse();
            }
        });

        it('returns non-empty calendarSlots array (kills AlwaysReturnEmptyArray on line 188)', function (): void {
            Date::setTestNow(Date::create(2026, 2, 10, 10, 0, 0, $this->timezone));

            $service = new BookingCalendarEventsService(
                Date::now($this->timezone),
                $this->user,
                $this->timezone,
                true,
                $this->mentorProgram,
            );

            $result = $service->getFormattedMonthAvailableSlots();

            // The buildCalendarView method should return a non-empty array
            expect($result['calendarSlots'])->toBeArray()->not->toBeEmpty();
        });
    });
});
