<?php

declare(strict_types=1);

use App\Enums\UserScheduleRecordType;
use App\Http\Resources\UserSchedule\UserScheduleViewResource;
use App\Models\UserSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

mutates(UserScheduleViewResource::class);

describe('UserScheduleViewResource', function (): void {
    it('transforms user schedule to array with all required fields', function (): void {
        $schedule = UserSchedule::factory()->make([
            'id'           => 123,
            'user_id'      => 456,
            'day_of_week'  => 1,
            'start_time'   => '09:00:00',
            'end_time'     => '17:00:00',
            'type'         => UserScheduleRecordType::ALL_WORKING_DAYS,
            'day_off_date' => null,
            'timezone'     => 'Europe/Kyiv',
        ]);

        $schedule->id = 123;

        $resource = new UserScheduleViewResource($schedule);
        $request = Request::create('/test');
        $result = $resource->toArray($request);

        expect($result)->toBeArray()
            ->toHaveKeys(['id', 'user_id', 'day_of_week', 'start_time', 'end_time', 'type', 'day_off_date', 'timezone'])
            ->and($result['id'])->toBe(123)
            ->and($result['user_id'])->toBe(456)
            ->and($result['day_of_week'])->toBe(1)
            ->and($result['start_time'])->toBe('09:00:00')
            ->and($result['end_time'])->toBe('17:00:00')
            ->and($result['type'])->toBeInstanceOf(UserScheduleRecordType::class)
            ->and($result['day_off_date'])->toBeNull()
            ->and($result['timezone'])->toBe('Europe/Kyiv');
    });

    it('formats day_off_date as Y-m-d when present', function (): void {
        $dayOffDate = Date::now()->addWeek();

        $schedule = UserSchedule::factory()->make([
            'type'         => UserScheduleRecordType::DAY_OFF,
            'day_off_date' => $dayOffDate,
        ]);

        $schedule->id = 1;

        $resource = new UserScheduleViewResource($schedule);
        $request = Request::create('/test');
        $result = $resource->toArray($request);

        expect($result['day_off_date'])->toBe($dayOffDate->format('Y-m-d'));
    });

    it('returns null for day_off_date when not present', function (): void {
        $schedule = UserSchedule::factory()->make([
            'type'         => UserScheduleRecordType::ALL_WORKING_DAYS,
            'day_off_date' => null,
        ]);

        $schedule->id = 1;

        $resource = new UserScheduleViewResource($schedule);
        $request = Request::create('/test');
        $result = $resource->toArray($request);

        expect($result['day_off_date'])->toBeNull();
    });

    it('uses getKey method for id field', function (): void {
        $schedule = UserSchedule::factory()->make();
        $schedule->id = 999;

        $resource = new UserScheduleViewResource($schedule);
        $request = Request::create('/test');
        $result = $resource->toArray($request);

        expect($result['id'])->toBe(999);
    });

    it('preserves type as enum instance', function (): void {
        $schedule = UserSchedule::factory()->make([
            'type' => UserScheduleRecordType::DAY_OFF,
        ]);

        $schedule->id = 1;

        $resource = new UserScheduleViewResource($schedule);
        $request = Request::create('/test');
        $result = $resource->toArray($request);

        expect($result['type'])->toBeInstanceOf(UserScheduleRecordType::class)
            ->and($result['type'])->toBe(UserScheduleRecordType::DAY_OFF);
    });

    it('can be used in collection', function (): void {
        $schedules = UserSchedule::factory()->count(3)->make()->each(function ($schedule, $index): void {
            $schedule->id = $index + 1;
        });

        $collection = UserScheduleViewResource::collection($schedules);
        $result = $collection->resolve();

        expect($result)->toBeArray()
            ->toHaveCount(3);

        foreach ($result as $item) {
            expect($item)->toBeArray()
                ->toHaveKeys(['id', 'user_id', 'day_of_week', 'start_time', 'end_time', 'type', 'day_off_date', 'timezone']);
        }
    });

    it('handles different timezones correctly', function (): void {
        $timezones = ['America/New_York', 'Asia/Tokyo', 'Europe/London', 'UTC'];

        foreach ($timezones as $timezone) {
            $schedule = UserSchedule::factory()->make([
                'timezone' => $timezone,
            ]);

            $schedule->id = 1;

            $resource = new UserScheduleViewResource($schedule);
            $request = Request::create('/test');
            $result = $resource->toArray($request);

            expect($result['timezone'])->toBe($timezone);
        }
    });

    it('handles all day_of_week values correctly', function (): void {
        for ($day = 0; $day <= 6; $day++) {
            $schedule = UserSchedule::factory()->make([
                'day_of_week' => $day,
            ]);

            $schedule->id = 1;

            $resource = new UserScheduleViewResource($schedule);
            $request = Request::create('/test');
            $result = $resource->toArray($request);

            expect($result['day_of_week'])->toBe($day);
        }
    });
});
