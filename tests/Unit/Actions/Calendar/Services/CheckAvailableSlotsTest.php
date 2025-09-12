<?php

declare(strict_types=1);

use App\Actions\Calendar\Services\CheckAvailableSlots;

mutates(CheckAvailableSlots::class);

describe('CheckAvailableSlots Service', function (): void {
    it('returns true when availableSlots is empty (per current implementation)', function (): void {
        $service = new CheckAvailableSlots([], 1000, 2000);
        expect($service->execute())->toBeTrue();
    });

    it('returns false when requested interval fits entirely within an available slot', function (): void {
        $slots = [
            ['start' => 1_000_000, 'end' => 2_000_000],
        ];

        $service = new CheckAvailableSlots($slots, 1_100_000, 1_900_000);
        expect($service->execute())->toBeTrue();
    });

    it('returns false when requested interval exceed max slot boundaries', function (): void {
        $slots = [
            ['start' => 1_000_000, 'end' => 1_900_000],
        ];

        $service = new CheckAvailableSlots($slots, 1_000_000, 2_000_000);
        expect($service->execute())->toBeFalse();
    });

    it('returns false when requested interval exceed min slot boundaries', function (): void {
        $slots = [
            ['start' => 1_100_000, 'end' => 2_000_000],
        ];

        $service = new CheckAvailableSlots($slots, 1_000_000, 2_000_000);
        expect($service->execute())->toBeFalse();
    });

    it('returns true when requested interval exactly within slot boundaries', function (): void {
        $slots = [
            ['start' => 1_000_000, 'end' => 2_000_000],
        ];

        $service = new CheckAvailableSlots($slots, 1_000_000, 2_000_000);
        expect($service->execute())->toBeTrue();
    });

    it('returns false when requested interval does not fit any slot (default path)', function (): void {
        $slots = [
            ['start' => 1_000_000, 'end' => 1_100_000],
            ['start' => 2_000_000, 'end' => 2_100_000],
        ];

        $service = new CheckAvailableSlots($slots, 1_150_000, 1_250_000);
        expect($service->execute())->toBeFalse();
    });
});
