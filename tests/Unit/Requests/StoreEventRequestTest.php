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
                ];
            }
        };

        $data = $request->getEventData();

        expect($data['type'])->toBe(EventTypeEnum::GROUP->value)
            ->and($data['status'])->toBe(EventStatusEnum::CONFIRMED)
            ->and($data['duration'])->toBe(5400);
    });
});
