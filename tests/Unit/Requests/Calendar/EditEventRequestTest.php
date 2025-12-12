<?php

declare(strict_types=1);
use App\Enums\CalendarEventColoursEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Http\Requests\Calendar\EditCalendarEventRequest;
use Illuminate\Support\Facades\Date;

it('successfully validates payload with all required info', function (): void {
    $payload = [
        'title'         => 'successfull validation',
        'fromDate'      => Date::now()->addDays(5)->format('Y-m-d'),
        'toDate'        => Date::now()->addDays(5)->format('Y-m-d'),
        'fromTime'      => '10:00',
        'toTime'        => '11:30',
        'type'          => CalendarEventTypeEnum::INDIVIDUAL->value,
        'colour'        => CalendarEventColoursEnum::BLUE->value,
    ];

    $request = new EditCalendarEventRequest;

    $validator = Validator::make($payload, $request->rules());

    expect($validator->passes())->toBeTrue();
    expect($validator->errors())->isEmpty();
});

it('rejects when title is missing', function (): void {
    $payload = [
        // 'title' => missing
        'fromDate' => Date::now()->addDays(5)->format('Y-m-d'),
        'toDate'   => Date::now()->addDays(5)->format('Y-m-d'),
        'fromTime' => '10:00',
        'toTime'   => '11:30',
        'type'     => 'individual',
        'colour'   => CalendarEventColoursEnum::BLUE->value,
    ];

    $request = new EditCalendarEventRequest;

    $validator = Validator::make($payload, $request->rules());

    expect($validator->passes())->toBeFalse();
    expect($validator->errors()->has('title'))->toBeTrue();
});

it('rejects when fromDate is wrong format', function (): void {
    $payload = [
        'title'    => 'Wrong fromDate format ',
        'fromDate' => '2025-01----01',
        'toDate'   => Date::now()->addDays(5)->format('Y-m-d'),
        'fromTime' => '10:00',
        'toTime'   => '11:30',
        'type'     => CalendarEventTypeEnum::INDIVIDUAL->value,
        'colour'   => CalendarEventColoursEnum::BLUE->value,
    ];

    $request = new EditCalendarEventRequest;

    $validator = Validator::make($payload, $request->rules());

    expect($validator->passes())->toBeFalse();
    expect($validator->errors()->has('fromDate'))->toBeTrue();
});

it('rejects when fromTime is wrong format', function (): void {
    $payload = [
        'title'    => 'Wrong fromDate format ',
        'fromDate' => Date::now()->addDays(5)->format('Y-m-d'),
        'toDate'   => Date::now()->addDays(5)->format('Y-m-d'),
        'fromTime' => '--:00',
        'toTime'   => '11:30',
        'type'     => CalendarEventTypeEnum::INDIVIDUAL->value,
        'colour'   => CalendarEventColoursEnum::BLUE->value,
    ];

    $request = new EditCalendarEventRequest;

    $validator = Validator::make($payload, $request->rules());

    expect($validator->passes())->toBeFalse();
    expect($validator->errors()->has('fromTime'))->toBeTrue();
});

it('rejects when toDate is wrong format', function (): void {
    $payload = [
        'title'    => 'Wrong fromDate format ',
        'fromDate' => Date::now()->addDays(5)->format('Y-m-d'),
        'toDate'   => '----01-01',
        'fromTime' => '10:00',
        'toTime'   => '11:30',
        'type'     => CalendarEventTypeEnum::INDIVIDUAL->value,
        'colour'   => CalendarEventColoursEnum::BLUE->value,
    ];

    $request = new EditCalendarEventRequest;

    $validator = Validator::make($payload, $request->rules());

    expect($validator->passes())->toBeFalse();
    expect($validator->errors()->has('toDate'))->toBeTrue();
});

it('rejects when toTime is wrong format', function (): void {
    $payload = [
        'title'    => 'Wrong fromDate format ',
        'fromDate' => Date::now()->addDays(5)->format('Y-m-d'),
        'toDate'   => Date::now()->addDays(5)->format('Y-m-d'),
        'fromTime' => '10:00',
        'toTime'   => '--:30',
        'type'     => CalendarEventTypeEnum::INDIVIDUAL->value,
        'colour'   => CalendarEventColoursEnum::BLUE->value,
    ];

    $request = new EditCalendarEventRequest;

    $validator = Validator::make($payload, $request->rules());

    expect($validator->passes())->toBeFalse();
    expect($validator->errors()->has('toTime'))->toBeTrue();
});

it('rejects when colour is not from list', function (): void {
    $payload = [
        'title'    => 'Wrong fromDate format ',
        'fromDate' => Date::now()->addDays(5)->format('Y-m-d'),
        'toDate'   => Date::now()->addDays(5)->format('Y-m-d'),
        'fromTime' => '10:00',
        'toTime'   => '11:30',
        'type'     => CalendarEventTypeEnum::INDIVIDUAL->value,
        'colour'   => 'wrong colour',
    ];

    $request = new EditCalendarEventRequest;

    $validator = Validator::make($payload, $request->rules());

    expect($validator->passes())->toBeFalse();
    expect($validator->errors()->has('colour'))->toBeTrue();
});
