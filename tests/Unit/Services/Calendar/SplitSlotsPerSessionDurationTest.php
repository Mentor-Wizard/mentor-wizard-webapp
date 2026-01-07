<?php

declare(strict_types=1);

use App\Services\Calendar\SplitSlotsPerSessionDuration;
use Illuminate\Support\Facades\Date;

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
        // Actually the period points will be 09:30 and 10:00 (excluding 09:00 start and 10:30 end)
        expect($split)->toHaveKey('2026-01-10');
        expect($split['2026-01-10'])->toHaveCount(2);
        expect($split['2026-01-10'][0]['start']->format('H:i'))->toBe('09:00')
            ->and($split['2026-01-10'][0]['end']->format('H:i'))->toBe('09:30')
            ->and($split['2026-01-10'][1]['start']->format('H:i'))->toBe('09:30')
            ->and($split['2026-01-10'][1]['end']->format('H:i'))->toBe('10:00');
    });

    it('does not split when slot duration equals session duration', function (): void {
        $tz = 'UTC';
        $start = Date::parse('2026-01-10 09:00:00', $tz);
        $end = Date::parse('2026-01-10 09:30:00', $tz); // 30 minutes equal to session

        $service = new SplitSlotsPerSessionDuration([
            ['start' => $start, 'end' => $end],
        ], 30, $tz);

        expect($service->getSplitSlots())->toBe([]);
    });

    it('splits across midnight and groups by target day', function (): void {
        $tz = 'UTC';
        $start = Date::parse('2026-01-10 23:30:00', $tz);
        $end = Date::parse('2026-01-11 01:00:00', $tz); // 90 minutes

        $service = new SplitSlotsPerSessionDuration([
            ['start' => $start, 'end' => $end],
        ], 30, $tz);

        $split = $service->getSplitSlots();

        // Period inner points are 2026-01-11 00:00 and 00:30; both belong to 2026-01-11
        expect($split)->toHaveKey('2026-01-11');
        expect($split['2026-01-11'])->toHaveCount(2);
        expect($split['2026-01-11'][0]['start']->format('Y-m-d H:i'))->toBe('2026-01-10 23:30')
            ->and($split['2026-01-11'][0]['end']->format('Y-m-d H:i'))->toBe('2026-01-11 00:00')
            ->and($split['2026-01-11'][1]['start']->format('Y-m-d H:i'))->toBe('2026-01-11 00:00')
            ->and($split['2026-01-11'][1]['end']->format('Y-m-d H:i'))->toBe('2026-01-11 00:30');
    });
});
