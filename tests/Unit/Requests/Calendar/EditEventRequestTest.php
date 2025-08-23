<?php

declare(strict_types=1);

use App\Enums\EventStatusEnum;
use App\Enums\EventTypeEnum;
use App\Http\Requests\Calendar\EditEventRequest;
use Illuminate\Support\Carbon;
use Mockery as m;

it('builds event data correctly for individual type', function (): void {
    $validated = [
        'title'       => 'Demo Event',
        'fromDate'    => '2025-01-01',
        'toDate'      => '2025-01-01',
        'fromTime'    => '10:00',
        'toTime'      => '11:30',
        'description' => 'Some description',
        // Note: Using lowercase here to assert mapping logic inside getEventData()
        'type' => 'individual',
    ];

    /** @var EditEventRequest|m\MockInterface $request */
    $request = m::mock(EditEventRequest::class)->makePartial();
    $request->shouldReceive('validated')->once()->andReturn($validated);

    $result = $request->getEventData();

    $expectedStart = Carbon::createFromFormat('Y-m-d H:i', '2025-01-01 10:00');
    $expectedEnd = Carbon::createFromFormat('Y-m-d H:i', '2025-01-01 11:30');

    expect($result)
        ->toBeArray()
        ->and($result['title'])->toBe('Demo Event')
        ->and($result['start_date_time'])->toEqual($expectedStart)
        ->and($result['end_date_time'])->toEqual($expectedEnd)
        ->and($result['duration'])->toBe($expectedStart->diffInSeconds($expectedEnd))
        ->and($result['type'])->toBe(EventTypeEnum::INDIVIDUAL->value)
        ->and($result['description'])->toBe('Some description')
        ->and($result['status'])->toBe(EventStatusEnum::CONFIRMED)
        ->and($result['date'])->toBe('2025-01-01');

    // UUID v4 format (basic check for 36-char hex + dashes)
    expect($result['unique_id'])
        ->toBeString()
        ->and(mb_strlen((string) $result['unique_id']))->toBe(36)
        ->and((bool) preg_match('/^[0-9a-f-]{36}$/i', (string) $result['unique_id']))->toBeTrue();
});

it('builds event data correctly for group type', function (): void {
    $validated = [
        'title'       => 'Group Session',
        'fromDate'    => '2025-02-10',
        'toDate'      => '2025-02-10',
        'fromTime'    => '09:15',
        'toTime'      => '10:00',
        'description' => 'Group event',
        'type'        => 'group',
    ];

    /** @var EditEventRequest|m\MockInterface $request */
    $request = m::mock(EditEventRequest::class)->makePartial();
    $request->shouldReceive('validated')->once()->andReturn($validated);

    $result = $request->getEventData();

    $expectedStart = Carbon::createFromFormat('Y-m-d H:i', '2025-02-10 09:15');
    $expectedEnd = Carbon::createFromFormat('Y-m-d H:i', '2025-02-10 10:00');

    expect($result)
        ->toBeArray()
        ->and($result['title'])->toBe('Group Session')
        ->and($result['start_date_time'])->toEqual($expectedStart)
        ->and($result['end_date_time'])->toEqual($expectedEnd)
        ->and($result['duration'])->toBe($expectedStart->diffInSeconds($expectedEnd))
        ->and($result['type'])->toBe(EventTypeEnum::GROUP->value)
        ->and($result['description'])->toBe('Group event')
        ->and($result['status'])->toBe(EventStatusEnum::CONFIRMED)
        ->and($result['date'])->toBe('2025-02-10');

    expect($result['unique_id'])
        ->toBeString()
        ->and(mb_strlen((string) $result['unique_id']))->toBe(36)
        ->and((bool) preg_match('/^[0-9a-f-]{36}$/i', (string) $result['unique_id']))->toBeTrue();
});
