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
});

describe('StoreEventRequest rules and messages', function (): void {
    it('provides all expected validation rules', function (): void {
        $request = new StoreEventRequest;
        $rules = $request->rules();

        expect($rules)
            ->toHaveKeys(['title', 'fromDate', 'toDate', 'fromTime', 'toTime', 'description', 'type'])
            ->and($rules['title'])->toContain('required', 'string', 'max:255')
            ->and($rules['fromDate'])->toContain('required', 'date', 'after_or_equal:today')
            ->and($rules['toDate'])->toContain('required', 'date', 'after_or_equal:fromDate')
            ->and($rules['fromTime'])->toContain('required', 'date_format:H:i')
            ->and($rules['toTime'])->toContain('required', 'date_format:H:i');
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
