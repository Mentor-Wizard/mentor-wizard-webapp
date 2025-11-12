<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Calendar\GetWeeklyEventsService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;

mutates(GetWeeklyEventsService::class);

describe('GetWeeklyEventsService Service', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    it('sets flags in week formatted calendar (isCurrentMonth, isSelected, isToday) using service', function (): void {
        // Freeze time to ensure deterministic behaviour
        Date::setTestNow(Date::create(2025, 1, 15, 12, 0, 0, 'UTC'));
        $tz = 'UTC';

        /** @var User $user */
        $user = User::factory()->create();

        $date = '2025-01-15'; // Wednesday

        $result = new GetWeeklyEventsService($user, $date, $tz)->execute();

        expect($result)
            ->toHaveKeys(['events', 'calendarView']);

        $entryForSelected = collect($result['calendarView'])
            ->firstWhere('date', $date);

        expect($entryForSelected)
            ->toBeArray()
            ->and($entryForSelected['isCurrentMonth'] ?? null)->toBeTrue()
            ->and($entryForSelected['isSelected'] ?? null)->toBeTrue()
            ->and($entryForSelected['isToday'] ?? null)->toBeTrue();
    });
});
