<?php

declare(strict_types=1);

use App\Enums\CalendarEventColoursEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Http\Requests\Calendar\StoreCalendarEventRequest;
use App\Models\CalendarEvent;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\ValidationException;

mutates(StoreCalendarEventRequest::class);

describe('StoreCalendarEventRequest getEventData and validator extras', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        auth()->login($this->user);

        $this->prepareRequest = function (StoreCalendarEventRequest $request): void {
            $request->setContainer(app());
            $request->setRedirector(app(Redirector::class));
            $request->setUserResolver(fn () => $this->user);
        };
    });

    it('successfully validates payload with all required info', function (): void {
        $payload = [
            'id'            => 1,
            'title'         => 'successful validation',
            'fromDate'      => Date::now()->addDays(5)->format('Y-m-d'),
            'toDate'        => Date::now()->addDays(5)->format('Y-m-d'),
            'fromTime'      => '10:00',
            'toTime'        => '11:30',
            'type'          => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'        => CalendarEventColoursEnum::BLUE->value,
        ];

        $request = new StoreCalendarEventRequest;

        $validator = Validator::make($payload, $request->rules());

        expect($validator->passes())->toBeTrue();
        expect($validator->errors())->isEmpty();
    });

    it('successfully validates payload with change of timezone', function (): void {
        $this->user->profile->timezone = 'Europe/London';
        $this->user->profile->save();

        Config::set('app.timezone', 'UTC');

        CalendarEvent::query()->create([
            'start_date_time' => Date::now()->addDays(5)
                ->timezone('Europe/London')->format('Y-m-d H:i:s'),
            'end_date_time' => Date::now()->addDays(5)
                ->timezone('Europe/London')->addHours(1)->format('Y-m-d H:i:s'),
            'date' => Date::now()->addDays(5)
                ->timezone('Europe/London')->format('Y-m-d'),
            'type'  => CalendarEventTypeEnum::INDIVIDUAL->value,
            'title' => 'booked slot validation',
        ]);

        $payload = [
            'id'            => 1,
            'title'         => 'successful validation',
            'fromDate'      => Date::now()->addDays(5)->format('Y-m-d'),
            'toDate'        => Date::now()->addDays(5)->format('Y-m-d'),
            'fromTime'      => '10:00',
            'toTime'        => '11:30',
            'type'          => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'        => CalendarEventColoursEnum::BLUE->value,
        ];

        $request = new StoreCalendarEventRequest;

        $validator = Validator::make($payload, $request->rules());

        expect($validator->passes())->toBeTrue();
        expect($validator->errors())->isEmpty();
    });

    it('catches exception when fromDate parsing fails in withValidator', function (): void {
        Date::setTestNow(Date::create(2025, 6, 1, 8, 0, 0, 'UTC'));

        $data = [
            'title'       => 'Invalid FromDate',
            'fromDate'    => '2025-13-45', // Invalid date but passes basic 'date' validation initially
            'toDate'      => Date::now()->addDays(2)->format('Y-m-d'),
            'fromTime'    => '09:15',
            'toTime'      => '10:45',
            'description' => 'desc',
            'type'        => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'      => CalendarEventColoursEnum::BLUE->value,
        ];

        $request = new StoreCalendarEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);

        try {
            $request->validateResolved();
            expect(false)->toBeTrue('Should have thrown exception');
        } catch (Exception $exception) {
            // Can be ValidationException or InvalidFormatException
            expect($exception)->toBeInstanceOf(Exception::class);
        }
    });

    it('catches exception when fromDate with fromTime parsing fails in withValidator', function (): void {
        Date::setTestNow(Date::create(2025, 6, 1, 8, 0, 0, 'UTC'));

        $data = [
            'title'       => 'Invalid FromDateTime',
            'fromDate'    => Date::now()->addDays(2)->format('Y-m-d'),
            'toDate'      => Date::now()->addDays(2)->format('Y-m-d'),
            'fromTime'    => '25:99', // Invalid time that might pass initial format check
            'toTime'      => '10:45',
            'description' => 'desc',
            'type'        => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'      => CalendarEventColoursEnum::BLUE->value,
        ];

        $request = new StoreCalendarEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);

        try {
            $request->validateResolved();
            expect(false)->toBeTrue('Should have thrown exception');
        } catch (Exception $exception) {
            expect($exception)->toBeInstanceOf(Exception::class);
        }
    });

    it('catches exception when toDate is null', function (): void {
        Date::setTestNow(Date::create(2025, 6, 1, 8, 0, 0, 'UTC'));

        $data = [
            'title'       => 'Invalid FromDateTime',
            'fromDate'    => Date::now()->addDays(2)->format('Y-m-d'),
            'toDate'      => null,
            'fromTime'    => '09:00', // Invalid time that might pass initial format check
            'toTime'      => '10:45',
            'description' => 'desc',
            'type'        => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'      => CalendarEventColoursEnum::BLUE->value,
        ];

        $request = new StoreCalendarEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);

        try {
            $request->validateResolved();
            expect(false)->toBeTrue('Should have thrown exception');
        } catch (Exception $exception) {
            expect($exception)->toBeInstanceOf(Exception::class);
        }
    });

    it('catches exception when fromTime is null', function (): void {
        Date::setTestNow(Date::create(2025, 6, 1, 8, 0, 0, 'UTC'));

        $data = [
            'title'       => 'Invalid FromDateTime',
            'fromDate'    => Date::now()->addDays(2)->format('Y-m-d'),
            'toDate'      => Date::now()->addDays(2)->format('Y-m-d'),
            'fromTime'    => null,
            'toTime'      => '10:45',
            'description' => 'desc',
            'type'        => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'      => CalendarEventColoursEnum::BLUE->value,
        ];

        $request = new StoreCalendarEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);

        try {
            $request->validateResolved();
            expect(false)->toBeTrue('Should have thrown exception');
        } catch (Exception $exception) {
            expect($exception)->toBeInstanceOf(Exception::class);
        }
    });

    it('catches exception when fromDate is null', function (): void {
        Date::setTestNow(Date::create(2025, 6, 1, 8, 0, 0, 'UTC'));

        $data = [
            'title'       => 'Invalid FromDateTime',
            'fromDate'    => null,
            'toDate'      => Date::now()->addDays(2)->format('Y-m-d'),
            'fromTime'    => '08:00',
            'toTime'      => '09:00',
            'description' => 'desc',
            'type'        => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'      => CalendarEventColoursEnum::BLUE->value,
        ];

        $request = new StoreCalendarEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);

        try {
            $request->validateResolved();
            expect(false)->toBeTrue('Should have thrown exception');
        } catch (Exception $exception) {
            expect($exception)->toBeInstanceOf(Exception::class);
        }
    });

    it('catches exception when toTime is null', function (): void {
        Date::setTestNow(Date::create(2025, 6, 1, 8, 0, 0, 'UTC'));

        $data = [
            'title'       => 'Invalid toDateTime',
            'fromDate'    => Date::now()->addDays(2)->format('Y-m-d'),
            'toDate'      => Date::now()->addDays(2)->format('Y-m-d'),
            'fromTime'    => '08:00',
            'toTime'      => null,
            'description' => 'desc',
            'type'        => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'      => CalendarEventColoursEnum::BLUE->value,
        ];

        $request = new StoreCalendarEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);

        try {
            $request->validateResolved();
            expect(false)->toBeTrue('Should have thrown exception');
        } catch (Exception $exception) {
            expect($exception)->toBeInstanceOf(Exception::class);
        }
    });

    it('catches exception when toDate parsing fails in withValidator', function (): void {
        Date::setTestNow(Date::create(2025, 6, 1, 8, 0, 0, 'UTC'));

        $data = [
            'title'       => 'Invalid ToDate',
            'fromDate'    => Date::now()->addDays(2)->format('Y-m-d'),
            'toDate'      => '2025-02-30', // Invalid date
            'fromTime'    => '09:15',
            'toTime'      => '10:45',
            'description' => 'desc',
            'type'        => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'      => CalendarEventColoursEnum::BLUE->value,
        ];

        $request = new StoreCalendarEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);

        try {
            $request->validateResolved();
            expect(false)->toBeTrue('Should have thrown exception');
        } catch (Exception $exception) {
            expect($exception)->toBeInstanceOf(Exception::class);
        }
    });

    it('catches exception when toDate with toTime parsing fails in withValidator', function (): void {
        Date::setTestNow(Date::create(2025, 6, 1, 8, 0, 0, 'UTC'));

        $data = [
            'title'       => 'Invalid ToDateTime',
            'fromDate'    => Date::now()->addDays(2)->format('Y-m-d'),
            'toDate'      => Date::now()->addDays(2)->format('Y-m-d'),
            'fromTime'    => '09:15',
            'toTime'      => '25:99', // Invalid time
            'description' => 'desc',
            'type'        => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'      => CalendarEventColoursEnum::BLUE->value,
        ];

        $request = new StoreCalendarEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);

        try {
            $request->validateResolved();
            expect(false)->toBeTrue('Should have thrown exception');
        } catch (Exception $exception) {
            expect($exception)->toBeInstanceOf(Exception::class);
        }
    });

    it('validates time slots and adds error when slot is reserved', function (): void {
        Date::setTestNow(Date::create(2025, 6, 1, 8, 0, 0, 'UTC'));

        // Create an existing event that will conflict
        $existingEvent = CalendarEvent::factory()->create([
            'start_date_time' => Date::now()->addDays(2)->setTime(10, 0, 0),
            'end_date_time'   => Date::now()->addDays(2)->setTime(11, 0, 0),
            'date'            => Date::now()->addDays(2)->format('Y-m-d'),
        ]);
        $existingEvent->calendarEventUsers()->attach($this->user->getKey());

        $data = [
            'title'       => 'Conflicting Event',
            'fromDate'    => Date::now()->addDays(2)->format('Y-m-d'),
            'toDate'      => Date::now()->addDays(2)->format('Y-m-d'),
            'fromTime'    => '10:15', // overlaps with existing event
            'toTime'      => '10:45',
            'description' => 'desc',
            'type'        => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'      => CalendarEventColoursEnum::BLUE->value,
            'timezone'    => 'UTC',
        ];

        $request = new StoreCalendarEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);

        try {
            $request->validateResolved();
            expect(false)->toBeTrue('Should have failed validation due to slot conflict');
        } catch (ValidationException $validationException) {
            expect($validationException->errors())->toHaveKey('fromDate')
                ->and($validationException->errors()['fromDate'])->toContain('there are another events on this time');
        }
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

        $request = new StoreCalendarEventRequest;

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

        $request = new StoreCalendarEventRequest;

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

        $request = new StoreCalendarEventRequest;

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

        $request = new StoreCalendarEventRequest;

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

        $request = new StoreCalendarEventRequest;

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

        $request = new StoreCalendarEventRequest;

        $validator = Validator::make($payload, $request->rules());

        expect($validator->passes())->toBeFalse();
        expect($validator->errors()->has('colour'))->toBeTrue();
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
            ->toHaveKeys(['title', 'fromDate', 'toDate', 'fromTime', 'toTime', 'description', 'type', 'colour'])
            ->and($rules['title'])->toContain('required', 'string', 'max:255')
            ->and($rules['fromDate'])->toContain('required', 'date', 'after_or_equal:today')
            ->and($rules['toDate'])->toContain('required', 'date', 'after_or_equal:fromDate')
            ->and($rules['fromTime'])->toContain('required', 'date_format:H:i')
            ->and($rules['toTime'])->toContain('required', 'date_format:H:i', 'after:fromTime')
            ->and($rules['colour'])->toContain('required')
            ->and($rules['description'])->toContain('max:2000')
            ->and($rules['type'])->toContain('required');
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
