<?php

declare(strict_types=1);

use App\Actions\Calendar\Services\GetAvailableSlots;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Carbon;

mutates(GetAvailableSlots::class);

describe('GetAvailableSlots Service', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    it('returns empty array when user has no future events', function (): void {
        Carbon::setTestNow(Carbon::create(2025, 4, 1, 10, 0, 0, 'UTC'));
        $user = User::factory()->create();

        $slots = new GetAvailableSlots($user, 'Europe/Kyiv')->execute();

        expect($slots)->toBeArray()->toBeEmpty();
    });

    it('builds available slots between events using timezone conversion', function (): void {
        // now is fixed in UTC
        Carbon::setTestNow(Carbon::create(2025, 4, 1, 10, 0, 0, 'UTC'));
        $tz = 'Europe/Kyiv';

        /** @var User $user */
        $user = User::factory()->create();

        // Create two future events in UTC
        $event1StartUtc = Carbon::create(2025, 4, 1, 12, 0, 0, 'UTC');
        $event1EndUtc = (clone $event1StartUtc)->addHour();
        $event2StartUtc = Carbon::create(2025, 4, 1, 15, 0, 0, 'UTC');
        $event2EndUtc = (clone $event2StartUtc)->addHours(2);

        $event1 = Event::query()->create([
            'title'           => 'E1',
            'status'          => 'confirmed',
            'start_date_time' => $event1StartUtc,
            'end_date_time'   => $event1EndUtc,
            'duration'        => $event1EndUtc->diffInSeconds($event1StartUtc),
            'date'            => $event1StartUtc->format('Y-m-d'),
            'type'            => 'individual',
        ]);
        $event2 = Event::query()->create([
            'title'           => 'E2',
            'status'          => 'confirmed',
            'start_date_time' => $event2StartUtc,
            'end_date_time'   => $event2EndUtc,
            'duration'        => $event2EndUtc->diffInSeconds($event2StartUtc),
            'date'            => $event2StartUtc->format('Y-m-d'),
            'type'            => 'individual',
        ]);
        $user->events()->attach([$event1->getKey(), $event2->getKey()]);

        $result = new GetAvailableSlots($user, $tz)->execute();

        // We expect 3 slots: [now..E1.start], [E1.end..E2.start], [E2.end..now+2months]
        expect($result)->toBeArray()->toHaveCount(3);

        // Slot 1 start is now in tz; end is E1 start in tz
        $slot1 = $result[0];
        expect($slot1['start'])->toBe(Carbon::now($tz)->timestamp)
            ->and($slot1['end'])->toBe($event1StartUtc->clone()->setTimezone($tz)->timestamp);

        // Slot 2 between E1 end and E2 start in tz
        $slot2 = $result[1];
        expect($slot2['start'])->toBe($event1EndUtc->clone()->setTimezone($tz)->timestamp)
            ->and($slot2['end'])->toBe($event2StartUtc->clone()->setTimezone($tz)->timestamp);

        // Slot 3 ends at now+2 months in tz
        $slot3 = $result[2];
        expect($slot3['start'])->toBe($event2EndUtc->clone()->setTimezone($tz)->timestamp)
            ->and($slot3['end'])->toBe(Carbon::now($tz)->addMonths(2)->timestamp);
    });

    it('does not create initial slot when first event starts exactly at current UTC time (strictly less than check)', function (): void {
        Carbon::setTestNow(Carbon::create(2025, 4, 1, 10, 0, 0, 'UTC'));
        $tz = 'Europe/Kyiv';
        $user = User::factory()->create();

        $eventStartUtc = Carbon::create(2025, 4, 1, 10, 0, 0, 'UTC');
        $eventEndUtc = (clone $eventStartUtc)->addHour();

        $event = Event::query()->create([
            'title'           => 'E-now',
            'status'          => 'confirmed',
            'start_date_time' => $eventStartUtc,
            'end_date_time'   => $eventEndUtc,
            'duration'        => $eventEndUtc->diffInSeconds($eventStartUtc),
            'date'            => $eventStartUtc->format('Y-m-d'),
            'type'            => 'individual',
        ]);
        $user->events()->attach($event->getKey());

        $result = new GetAvailableSlots($user, $tz)->execute();

        // Only two slots should exist: [E-now.end..now+2months]
        expect($result)->toBeArray()->toHaveCount(1)
            ->and($result[0]['start'])->toBe($eventEndUtc->clone()->setTimezone($tz)->timestamp)
            ->and($result[0]['end'])->toBe(Carbon::now($tz)->addMonths(2)->timestamp);
    });
});
