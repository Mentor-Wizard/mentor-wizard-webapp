<?php

declare(strict_types=1);

use App\Enums\EventStatusEnum;
use App\Enums\EventTypeEnum;
use App\Http\Requests\Calendar\StoreEventRequest;
use Illuminate\Support\Carbon;

mutates(StoreEventRequest::class);

describe('StoreEventRequest getEventData type mapping', function (): void {
    it('maps "individual" to EventTypeEnum::INDIVIDUAL value', function (): void {
        $request = new class extends StoreEventRequest
        {
            public function validated($key = null, $default = null): array
            {
                return [
                    'title'       => 'Test',
                    'fromDate'    => Carbon::today()->format('Y-m-d'),
                    'fromTime'    => '09:00',
                    'toDate'      => Carbon::today()->format('Y-m-d'),
                    'toTime'      => '10:00',
                    'type'        => 'individual',
                    'description' => 'Desc',
                    'timezone'    => 'Europe/Kyiv',
                ];
            }
        };

        $data = $request->getEventData();

        expect($data['type'])->toBe(EventTypeEnum::INDIVIDUAL->value)
            ->and($data['status'])->toBe(EventStatusEnum::CONFIRMED)
            ->and($data['duration'])->toBe(3600);
    });

    it('maps "group" to EventTypeEnum::GROUP value', function (): void {
        $request = new class extends StoreEventRequest
        {
            public function validated($key = null, $default = null): array
            {
                return [
                    'title'       => 'Test',
                    'fromDate'    => Carbon::today()->format('Y-m-d'),
                    'fromTime'    => '11:00',
                    'toDate'      => Carbon::today()->format('Y-m-d'),
                    'toTime'      => '12:30',
                    'type'        => 'group',
                    'description' => 'Desc',
                    'timezone'    => 'Europe/Kyiv',
                ];
            }
        };

        $data = $request->getEventData();

        expect($data['type'])->toBe(EventTypeEnum::GROUP->value)
            ->and($data['status'])->toBe(EventStatusEnum::CONFIRMED)
            ->and($data['duration'])->toBe(5400);
    });

    it('returns null values when invalid datetime strings are provided (nullsafe operators)', function (): void {
        // Here createFromFormat will return false for start datetime and true for end
        $request = new class extends StoreEventRequest
        {
            public function validated($key = null, $default = null): array
            {
                return [
                    'title'       => 'Invalid',
                    'fromDate'    => 'not-a-date',   // invalid
                    'fromTime'    => 'xx:yy',        // invalid
                    'toDate'      => Carbon::today()->format('Y-m-d'),
                    'toTime'      => '01:00',
                    'type'        => 'individual',
                    'description' => 'Desc',
                    'timezone'    => 'UTC',
                ];
            }
        };

        $data = $request->getEventData();

        expect($data['duration'])->toBeNull()
            ->and($data['date'])->toBeNull()
            ->and($data['title'])->toBeNull()
            ->and($data['start_date_time'])->toBeNull()
            ->and($data['end_date_time'])->toBeNull()
            ->and($data['type'])->toBeNull()
            ->and($data['description'])->toBeNull()
            ->and($data['status'])->toBeNull();
    });

    it('builds exact start/end when crossing midnight to ensure both date and time are concatenated', function (): void {
        $today = Carbon::today();
        $tomorrow = $today->copy()->addDay();

        $request = new class($today, $tomorrow) extends StoreEventRequest
        {
            public function __construct(private readonly Carbon $today, private readonly Carbon $tomorrow) {}

            public function validated($key = null, $default = null): array
            {
                return [
                    'title'       => 'Cross Midnight',
                    'fromDate'    => $this->today->format('Y-m-d'),
                    'fromTime'    => '23:30',
                    'toDate'      => $this->tomorrow->format('Y-m-d'),
                    'toTime'      => '00:15',
                    'type'        => 'group',
                    'description' => 'Desc',
                    'timezone'    => 'UTC',
                ];
            }
        };

        $data = $request->getEventData();

        expect($data['start_date_time']->format('Y-m-d H:i'))->toBe($today->format('Y-m-d').' 23:30')
            ->and($data['end_date_time']->format('Y-m-d H:i'))->toBe($tomorrow->format('Y-m-d').' 00:15')
            ->and($data['duration'])->toBe(45 * 60);
    });
});

describe('StoreEventRequest rules and messages', function (): void {
    it('provides all expected validation rules', function (): void {
        $request = new StoreEventRequest;
        $rules = $request->rules();

        expect($rules)
            ->toHaveKeys(['title', 'fromDate', 'toDate', 'fromTime', 'toTime', 'description', 'type', 'timezone'])
            ->and($rules['title'])->toContain('required', 'string', 'max:255')
            ->and($rules['fromDate'])->toContain('required', 'date', 'after_or_equal:today')
            ->and($rules['toDate'])->toContain('required', 'date', 'after_or_equal:fromDate')
            ->and($rules['fromTime'])->toContain('required', 'date_format:H:i')
            ->and($rules['toTime'])->toContain('required', 'date_format:H:i')
            ->and($rules['timezone'])->toContain('required', 'string');
    });

    it('provides all expected messages', function (): void {
        $request = new StoreEventRequest;
        $messages = $request->messages();

        expect($messages)
            ->toHaveKeys([
                'title.required',
                'title.max',
                'fromDate.required',
                'fromDate.date',
                'fromDate.after_or_equal',
                'toDate.required',
                'toDate.date',
                'toDate.after_or_equal',
                'fromTime.required',
                'fromTime.date_format',
                'toTime.required',
                'toTime.date_format',
                'toTime.after',
                'description.max',
                'type.required',
                'type.in',
            ]);
    });
});
