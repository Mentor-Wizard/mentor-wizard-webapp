<?php

declare(strict_types=1);

use App\Enums\CalendarEventColoursEnum;
use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Http\Requests\Calendar\StoreCalendarEventRequest;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;

mutates(StoreCalendarEventRequest::class);

describe('StoreCalendarEventRequest getEventData type mapping', function (): void {
    it('maps "individual" to CalendarEventTypeEnum::INDIVIDUAL value', function (): void {
        $request = new class extends StoreCalendarEventRequest
        {
            public function validated($key = null, $default = null): array
            {
                return [
                    'title'       => 'Test',
                    'fromDate'    => Date::today()->format('Y-m-d'),
                    'fromTime'    => '09:00',
                    'toDate'      => Date::today()->format('Y-m-d'),
                    'toTime'      => '10:00',
                    'type'        => 'individual',
                    'description' => 'Desc',
                    'colour'      => CalendarEventColoursEnum::BLUE->value,
                    'timezone'    => 'Europe/Kyiv',
                ];
            }
        };

        $data = $request->getEventData();

        expect($data['type'])->toBe(CalendarEventTypeEnum::INDIVIDUAL->value)
            ->and($data['status'])->toBe(CalendarEventStatusEnum::CONFIRMED)
            ->and($data['duration'])->toBe(3600);
    });

    it('maps "group" to CalendarEventTypeEnum::GROUP value', function (): void {
        $request = new class extends StoreCalendarEventRequest
        {
            public function validated($key = null, $default = null): array
            {
                return [
                    'title'       => 'Test',
                    'fromDate'    => Date::today()->format('Y-m-d'),
                    'fromTime'    => '11:00',
                    'toDate'      => Date::today()->format('Y-m-d'),
                    'toTime'      => '12:30',
                    'type'        => 'group',
                    'description' => 'Desc',
                    'colour'      => CalendarEventColoursEnum::BLUE->value,
                    'timezone'    => 'Europe/Kyiv',
                ];
            }
        };

        $data = $request->getEventData();

        expect($data['type'])->toBe(CalendarEventTypeEnum::GROUP->value)
            ->and($data['status'])->toBe(CalendarEventStatusEnum::CONFIRMED)
            ->and($data['duration'])->toBe(5400);
    });

    it('returns null values when invalid datetime strings are provided (nullsafe operators)', function (): void {
        // Here createFromFormat will return false for start datetime and true for end
        $request = new class extends StoreCalendarEventRequest
        {
            public function validated($key = null, $default = null): array
            {
                return [
                    'title'       => 'Invalid',
                    'fromDate'    => 'not-a-date',   // invalid
                    'fromTime'    => 'xx:yy',        // invalid
                    'toDate'      => Date::today()->format('Y-m-d'),
                    'toTime'      => '01:00',
                    'type'        => 'individual',
                    'description' => 'Desc',
                    'colour'      => CalendarEventColoursEnum::BLUE->value,
                    'timezone'    => 'UTC',
                ];
            }
        };

        $data = $request->getEventData();

        expect($data)->toBeEmpty();
    });

    it('builds exact start/end when crossing midnight to ensure both date and time are concatenated', function (): void {
        $today = Date::today();
        $tomorrow = $today->copy()->addDay();

        $request = new class($today, $tomorrow) extends StoreCalendarEventRequest
        {
            public function __construct(private readonly CarbonInterface $today, private readonly CarbonInterface $tomorrow) {}

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
                    'colour'      => CalendarEventColoursEnum::BLUE->value,
                    'timezone'    => 'UTC',
                ];
            }
        };

        $data = $request->getEventData();

        expect($data['start_date_time']->format('Y-m-d H:i'))->toBe($today->format('Y-m-d').' 23:30')
            ->and($data['end_date_time']->format('Y-m-d H:i'))->toBe($tomorrow->format('Y-m-d').' 00:15')
            ->and($data['duration'])->toBe(45 * 60);
    });

    it('maps unknown type to CalendarEventTypeEnum::INDIVIDUAL value (default case)', function (): void {
        $request = new class extends StoreCalendarEventRequest
        {
            public function validated($key = null, $default = null): array
            {
                return [
                    'title'       => 'Unknown Type',
                    'fromDate'    => Date::today()->format('Y-m-d'),
                    'fromTime'    => '09:00',
                    'toDate'      => Date::today()->format('Y-m-d'),
                    'toTime'      => '10:00',
                    'type'        => 'unknown-type', // not 'individual' or 'group'
                    'description' => 'Desc',
                    'colour'      => CalendarEventColoursEnum::BLUE->value,
                    'timezone'    => 'UTC',
                ];
            }
        };

        $data = $request->getEventData();

        expect($data['type'])->toBe(CalendarEventTypeEnum::INDIVIDUAL->value);
    });

    it('verifies colour value is passed through to event data', function (): void {
        $request = new class extends StoreCalendarEventRequest
        {
            public function validated($key = null, $default = null): array
            {
                return [
                    'title'       => 'Colour Test',
                    'fromDate'    => Date::today()->format('Y-m-d'),
                    'fromTime'    => '09:00',
                    'toDate'      => Date::today()->format('Y-m-d'),
                    'toTime'      => '10:00',
                    'type'        => 'individual',
                    'description' => 'Test colour',
                    'colour'      => CalendarEventColoursEnum::RED->value,
                    'timezone'    => 'UTC',
                ];
            }
        };

        $data = $request->getEventData();

        expect($data['colour'])->toBe(CalendarEventColoursEnum::RED->value);
    });

    it('verifies title value is passed through to event data', function (): void {
        $request = new class extends StoreCalendarEventRequest
        {
            public function validated($key = null, $default = null): array
            {
                return [
                    'title'       => 'Specific Title',
                    'fromDate'    => Date::today()->format('Y-m-d'),
                    'fromTime'    => '09:00',
                    'toDate'      => Date::today()->format('Y-m-d'),
                    'toTime'      => '10:00',
                    'type'        => 'individual',
                    'description' => 'Desc',
                    'colour'      => CalendarEventColoursEnum::BLUE->value,
                    'timezone'    => 'UTC',
                ];
            }
        };

        $data = $request->getEventData();

        expect($data['title'])->toBe('Specific Title');
    });

    it('verifies status is always set to CONFIRMED', function (): void {
        $request = new class extends StoreCalendarEventRequest
        {
            public function validated($key = null, $default = null): array
            {
                return [
                    'title'       => 'Status Test',
                    'fromDate'    => Date::today()->format('Y-m-d'),
                    'fromTime'    => '09:00',
                    'toDate'      => Date::today()->format('Y-m-d'),
                    'toTime'      => '10:00',
                    'type'        => 'individual',
                    'description' => 'Desc',
                    'colour'      => CalendarEventColoursEnum::BLUE->value,
                    'timezone'    => 'UTC',
                ];
            }
        };

        $data = $request->getEventData();

        expect($data['status'])->toBe(CalendarEventStatusEnum::CONFIRMED);
    });

    it('verifies date field is formatted correctly from start datetime', function (): void {
        $request = new class extends StoreCalendarEventRequest
        {
            public function validated($key = null, $default = null): array
            {
                return [
                    'title'       => 'Date Format Test',
                    'fromDate'    => '2025-12-25',
                    'fromTime'    => '14:30',
                    'toDate'      => '2025-12-25',
                    'toTime'      => '16:00',
                    'type'        => 'individual',
                    'description' => 'Desc',
                    'colour'      => CalendarEventColoursEnum::BLUE->value,
                    'timezone'    => 'UTC',
                ];
            }
        };

        $data = $request->getEventData();

        expect($data['date'])->toBe('2025-12-25');
    });
});

describe('StoreCalendarEventRequest rules and messages', function (): void {
    it('authorizes all requests', function (): void {
        $request = new StoreCalendarEventRequest;

        expect($request->authorize())->toBeTrue();
    });

    it('provides all expected validation rules', function (): void {
        $request = new StoreCalendarEventRequest;
        $rules = $request->rules();

        expect($rules)
            ->toHaveKeys(['title', 'fromDate', 'toDate', 'fromTime', 'toTime', 'description', 'type', 'timezone', 'colour'])
            ->and($rules['title'])->toContain('required', 'string', 'max:255')
            ->and($rules['fromDate'])->toContain('required', 'date', 'after_or_equal:today')
            ->and($rules['toDate'])->toContain('required', 'date', 'after_or_equal:fromDate')
            ->and($rules['fromTime'])->toContain('required', 'date_format:H:i')
            ->and($rules['toTime'])->toContain('required', 'date_format:H:i', 'after:fromTime')
            ->and($rules['colour'])->toContain('required')
            ->and($rules['description'])->toContain('max:2000')
            ->and($rules['type'])->toContain('required')
            ->and($rules['timezone'])->toContain('required', 'string');
    });

    it('provides all expected messages', function (): void {
        $request = new StoreCalendarEventRequest;
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
                'colour.required',
                'fromTime.date_format',
                'toTime.required',
                'toTime.date_format',
                'toTime.after',
                'description.max',
                'type.required',
                'type.in',
            ]);
    });

    it('validates that toTime must be after fromTime', function (): void {
        $request = new StoreCalendarEventRequest;
        $rules = $request->rules();

        expect($rules['toTime'])->toContain('after:fromTime');
    });

    it('validates colour is in allowed values', function (): void {
        $request = new StoreCalendarEventRequest;
        $rules = $request->rules();

        expect($rules['colour'])->toHaveCount(2)
            ->and($rules['colour'][0])->toBe('required');
    });

    it('validates type is in allowed values', function (): void {
        $request = new StoreCalendarEventRequest;
        $rules = $request->rules();

        expect($rules['type'])->toHaveCount(2)
            ->and($rules['type'][0])->toBe('required');
    });

    it('validates description max length is 2000', function (): void {
        $request = new StoreCalendarEventRequest;
        $rules = $request->rules();

        expect($rules['description'])->toContain('max:2000');
    });
});
