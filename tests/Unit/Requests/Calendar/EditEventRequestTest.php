<?php

declare(strict_types=1);

use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Http\Requests\Calendar\EditCalendarEventRequest;
use Illuminate\Support\Facades\Date;
use Mockery as m;

it('builds event data correctly for individual type', function (): void {
    $validated = [
        'title'       => 'Demo CalendarEvent',
        'fromDate'    => '2025-01-01',
        'toDate'      => '2025-01-01',
        'fromTime'    => '10:00',
        'toTime'      => '11:30',
        'description' => 'Some description',
        'type'        => 'individual',
        'colour'      => 'blue',
        'timezone'    => 'Europe/Kyiv',
    ];

    /** @var EditCalendarEventRequest|m\MockInterface $request */
    $request = m::mock(EditCalendarEventRequest::class)->makePartial();
    $request->shouldReceive('validated')->once()->andReturn($validated);

    $result = $request->getEventData();

    $expectedStart = Date::createFromFormat('Y-m-d H:i', '2025-01-01 10:00', 'Europe/Kyiv');
    $expectedEnd = Date::createFromFormat('Y-m-d H:i', '2025-01-01 11:30', 'Europe/Kyiv');

    expect($result)
        ->toBeArray()
        ->and($result['title'])->toBe('Demo CalendarEvent')
        ->and($result['start_date_time'])->setTimezone('Europe/Kyiv')->toEqual($expectedStart)
        ->and($result['end_date_time'])->setTimezone('Europe/Kyiv')->toEqual($expectedEnd)
        ->and($result['duration'])->toBe($expectedStart?->diffInSeconds($expectedEnd))
        ->and($result['type'])->toBe(CalendarEventTypeEnum::INDIVIDUAL->value)
        ->and($result['description'])->toBe('Some description')
        ->and($result['status'])->toBe(CalendarEventStatusEnum::CONFIRMED)
        ->and($result['date'])->toBe('2025-01-01');
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
        'colour'      => 'blue',
        'timezone'    => 'Europe/Kyiv',
    ];

    /** @var EditCalendarEventRequest|m\MockInterface $request */
    $request = m::mock(EditCalendarEventRequest::class)->makePartial();
    $request->shouldReceive('validated')->once()->andReturn($validated);

    $result = $request->getEventData();

    $expectedStart = Date::createFromFormat('Y-m-d H:i', '2025-02-10 09:15', 'Europe/Kyiv');
    $expectedEnd = Date::createFromFormat('Y-m-d H:i', '2025-02-10 10:00', 'Europe/Kyiv');

    expect($result)
        ->toBeArray()
        ->and($result['title'])->toBe('Group Session')
        ->and($result['start_date_time'])->setTimezone('Europe/Kyiv')->toEqual($expectedStart)
        ->and($result['end_date_time'])->setTimezone('Europe/Kyiv')->toEqual($expectedEnd)
        ->and($result['duration'])->toBe($expectedStart?->diffInSeconds($expectedEnd))
        ->and($result['type'])->toBe(CalendarEventTypeEnum::GROUP->value)
        ->and($result['description'])->toBe('Group event')
        ->and($result['status'])->toBe(CalendarEventStatusEnum::CONFIRMED)
        ->and($result['date'])->toBe('2025-02-10');
});
