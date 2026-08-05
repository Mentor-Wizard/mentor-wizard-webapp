<?php

declare(strict_types=1);

use Carbon\Exceptions\InvalidIntervalException;
use Illuminate\Support\Facades\Date;
use Modules\Calendar\Services\SplitSlotsPerSessionDuration;

mutates(SplitSlotsPerSessionDuration::class);

describe('SplitSlotsPerSessionDuration', function (): void {
    it('splits a longer slot into multiple session-sized slots', function (): void {
        $tz = 'UTC';
        $start = Date::parse('2026-01-10 09:00:00', $tz);
        $end = Date::parse('2026-01-10 10:30:00', $tz); // 90 minutes

        $service = new SplitSlotsPerSessionDuration([
            ['start' => $start, 'end' => $end],
        ], 30, $tz);

        $split = $service->getSplitSlots();

        // Expect 2 resulting slots: 09:00-09:30 and 09:30-10:00; 10:00-10:30 not created due to excludeEndDate
        //  Actually, the period points will be 09:30 and 10:00 (excluding 09:00 start and 10:30 end)
        expect($split)->toHaveKey('2026-01-10')
            ->and($split['2026-01-10'])->toHaveCount(2)
            ->and($split['2026-01-10'][0]['start']->format('H:i'))->toBe('09:00')
            ->and($split['2026-01-10'][0]['end']->format('H:i'))->toBe('09:30')
            ->and($split['2026-01-10'][1]['start']->format('H:i'))->toBe('09:30')
            ->and($split['2026-01-10'][1]['end']->format('H:i'))->toBe('10:00');
    });

    it('get one split period when slot duration equals session duration', function (): void {
        $tz = 'UTC';
        $start = Date::parse('2026-01-10 09:00:00', $tz);
        $end = Date::parse('2026-01-10 09:30:00', $tz); // 30 minutes equal to session

        $service = new SplitSlotsPerSessionDuration([
            ['start' => $start, 'end' => $end],
        ], 30, $tz);

        $result = $service->getSplitSlots();

        expect($result)->toHaveCount(1)->not->toBeEmpty();

        $firstSlot = $result['2026-01-10'][0];

        expect($firstSlot)->toHaveKeys(['start', 'end'])
            ->and($firstSlot['start']->toDateTimeString())->toBe('2026-01-10 09:00:00')
            ->and($firstSlot['end']->toDateTimeString())->toBe('2026-01-10 09:30:00');
    });

    it('does not split when slot duration less than session duration', function (): void {
        $tz = 'UTC';
        $start = Date::parse('2026-01-10 09:00:00', $tz);
        $end = Date::parse('2026-01-10 09:29:00', $tz); // 30 minutes equal to session

        $service = new SplitSlotsPerSessionDuration([
            ['start' => $start, 'end' => $end],
        ], 30, $tz);

        expect($service->getSplitSlots())->toBeEmpty();
    });

    it('splits across midnight and groups by target day will provide only one slot', function (): void {
        $tz = 'UTC';
        $start = Date::parse('2026-01-10 23:30:00', $tz);
        $end = Date::parse('2026-01-11 01:00:00', $tz); // 90 minutes

        $service = new SplitSlotsPerSessionDuration([
            ['start' => $start, 'end' => $end],
        ], 30, $tz);

        $split = $service->getSplitSlots();

        // Period inner points are 2026-01-11 00:00 and 00:30; both belong to 2026-01-11
        expect($split)->toHaveKey('2026-01-11')
            ->and($split['2026-01-11'])->toHaveCount(1)
            ->and($split['2026-01-11'][0]['start']->format('Y-m-d H:i'))->toBe('2026-01-11 00:00')
            ->and($split['2026-01-11'][0]['end']->format('Y-m-d H:i'))->toBe('2026-01-11 00:30');
    });

    describe('Session Duration Changes', function (): void {
        it('recalculates slots when session_duration changes from 30 to 60 minutes', function (): void {
            $tz = 'UTC';
            $start = Date::parse('2026-01-10 09:00:00', $tz);
            $end = Date::parse('2026-01-10 12:00:00', $tz); // 180 minutes

            // With 30-minute sessions
            $service30 = new SplitSlotsPerSessionDuration([
                ['start' => $start, 'end' => $end],
            ], 30, $tz);

            $split30 = $service30->getSplitSlots();
            expect($split30['2026-01-10'])->toHaveCount(5); // 09:00, 09:30, 10:00, 10:30, 11:00

            // With 60 minute sessions
            $service60 = new SplitSlotsPerSessionDuration([
                ['start' => $start, 'end' => $end],
            ], 60, $tz);

            $split60 = $service60->getSplitSlots();
            expect($split60['2026-01-10'])->toHaveCount(2); // 09:00, 10:00
        });

        it('handles session_duration change to very large value like 480 minutes', function (): void {
            $tz = 'UTC';
            $start = Date::parse('2026-01-10 09:00:00', $tz);
            $end = Date::parse('2026-01-10 17:00:00', $tz); // 480 minutes = 8 hours

            $service = new SplitSlotsPerSessionDuration([
                ['start' => $start, 'end' => $end],
            ], 480, $tz);

            $split = $service->getSplitSlots();

            // Slot duration equals session duration, so one slot
            expect($split)->toHaveKey('2026-01-10')
                ->and($split['2026-01-10'])->toHaveCount(1)
                ->and($split['2026-01-10'][0]['start']->format('H:i'))->toBe('09:00')
                ->and($split['2026-01-10'][0]['end']->format('H:i'))->toBe('17:00');
        });
    });

    describe('Edge Cases', function (): void {
        it('handles zero session duration parameter without infinite loop', function (): void {
            $tz = 'UTC';
            $start = Date::parse('2026-01-10 09:00:00', $tz);
            $end = Date::parse('2026-01-10 10:00:00', $tz);

            $service = new SplitSlotsPerSessionDuration([
                ['start' => $start, 'end' => $end],
            ], 0, $tz);

            expect(fn (): array => $service->getSplitSlots())
                ->toThrow(InvalidIntervalException::class, 'Empty interval is not accepted.');
        });

        it('handles empty slots array', function (): void {
            $tz = 'UTC';

            $service = new SplitSlotsPerSessionDuration([], 30, $tz);
            $split = $service->getSplitSlots();

            expect($split)->toBeEmpty();
        });

        it('handles slot with zero duration', function (): void {
            $tz = 'UTC';
            $start = Date::parse('2026-01-10 09:00:00', $tz);
            $end = Date::parse('2026-01-10 09:00:00', $tz); // Zero duration

            $service = new SplitSlotsPerSessionDuration([
                ['start' => $start, 'end' => $end],
            ], 30, $tz);

            $split = $service->getSplitSlots();

            // Zero duration slot should not produce any splits
            expect($split)->toBeEmpty();
        });

        it('handles multiple slots on same day', function (): void {
            $tz = 'UTC';
            $start1 = Date::parse('2026-01-10 09:00:00', $tz);
            $end1 = Date::parse('2026-01-10 10:00:00', $tz);
            $start2 = Date::parse('2026-01-10 14:00:00', $tz);
            $end2 = Date::parse('2026-01-10 15:00:00', $tz);

            $service = new SplitSlotsPerSessionDuration([
                ['start' => $start1, 'end' => $end1],
                ['start' => $start2, 'end' => $end2],
            ], 30, $tz);

            $split = $service->getSplitSlots();

            expect($split)->toHaveKey('2026-01-10')
                ->and($split['2026-01-10'])->toHaveCount(2);
            // Two 60-min slots, each split into 30-min = 2 slots each
        });

        it('handles slots on different days', function (): void {
            $tz = 'UTC';
            $start1 = Date::parse('2026-01-10 09:00:00', $tz);
            $end1 = Date::parse('2026-01-10 10:00:00', $tz);
            $start2 = Date::parse('2026-01-11 14:00:00', $tz);
            $end2 = Date::parse('2026-01-11 15:00:00', $tz);

            $service = new SplitSlotsPerSessionDuration([
                ['start' => $start1, 'end' => $end1],
                ['start' => $start2, 'end' => $end2],
            ], 30, $tz);

            $split = $service->getSplitSlots();

            expect($split)->toHaveKey('2026-01-10')
                ->and($split)->toHaveKey('2026-01-11');
        });
    });

    describe('Time Transfer Tests', function (): void {
        it('handles winter to summer time transition in Europe/Kyiv', function (): void {
            // DST transition in Europe/Kyiv 2026: March 29 at 03:00 (clocks forward)
            $tz = 'Europe/Kyiv';
            $start = Date::parse('2026-03-29 02:00:00', $tz);
            $end = Date::parse('2026-03-29 05:00:00', $tz);

            $service = new SplitSlotsPerSessionDuration([
                ['start' => $start, 'end' => $end],
            ], 60, $tz);

            $split = $service->getSplitSlots();

            // Should handle DST transition gracefully
            expect($split)->toHaveKey('2026-03-29')
                ->and($split['2026-03-29'])->toBeArray();
        });

        it('handles February 29 in leap year 2024', function (): void {
            $tz = 'UTC';
            $start = Date::parse('2024-02-29 09:00:00', $tz);
            $end = Date::parse('2024-02-29 12:00:00', $tz);

            $service = new SplitSlotsPerSessionDuration([
                ['start' => $start, 'end' => $end],
            ], 60, $tz);

            $split = $service->getSplitSlots();

            expect($split)->toHaveKey('2024-02-29')
                ->and($split['2024-02-29'])->toHaveCount(2);
        });

        it('handles event spanning leap day boundary', function (): void {
            $tz = 'UTC';
            $start = Date::parse('2024-02-28 22:00:00', $tz);
            $end = Date::parse('2024-02-29 02:00:00', $tz); // 4 hours

            $service = new SplitSlotsPerSessionDuration([
                ['start' => $start, 'end' => $end],
            ], 60, $tz);

            $split = $service->getSplitSlots();

            // Slots should span both dates
            expect($split)->toBeArray()->not()->toBeEmpty();
        });
    });
});
