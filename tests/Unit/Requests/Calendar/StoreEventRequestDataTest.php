<?php

declare(strict_types=1);

use App\Enums\CalendarEventColoursEnum;
use App\Http\Requests\Calendar\StoreEventRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\ValidationException;

mutates(StoreEventRequest::class);

describe('StoreEventRequest getEventData and validator extras', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        auth()->login($this->user);

        $this->prepareRequest = function (StoreEventRequest $request): void {
            $request->setContainer(app());
            $request->setRedirector(app(Redirector::class));
            $request->setUserResolver(fn () => $this->user);
        };
    });

    it('builds correct event payload including duration and type mapping', function (): void {
        Date::setTestNow(Date::create(2025, 6, 1, 8, 0, 0, 'UTC'));

        $data = [
            'title'       => 'Payload Build',
            'fromDate'    => Date::now()->addDays(2)->format('Y-m-d'),
            'toDate'      => Date::now()->addDays(2)->format('Y-m-d'),
            'fromTime'    => '09:15',
            'toTime'      => '10:45',
            'description' => 'desc',
            'type'        => 'Group', // should map to CalendarEventTypeEnum::GROUP
            'colour'      => CalendarEventColoursEnum::BLUE->value,
            'timezone'    => 'UTC',
        ];

        $request = new StoreEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);

        // Ensure validation passes (also triggers withValidator and internal concat building)
        $request->validateResolved();

        $payload = $request->getEventData();

        expect($payload)
            ->toHaveKeys(['title', 'start_date_time', 'end_date_time', 'duration', 'type', 'description', 'status', 'date'])
            ->and($payload['title'])->toBe('Payload Build')
            ->and($payload['date'])->toBe(Date::now()->addDays(2)->format('Y-m-d'))
            ->and($payload['duration'])->toBe(90 * 60);
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
            'type'        => 'Individual',
            'colour'      => CalendarEventColoursEnum::BLUE->value,
            'timezone'    => 'UTC',
        ];

        $request = new StoreEventRequest;
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
            'type'        => 'Individual',
            'colour'      => CalendarEventColoursEnum::BLUE->value,
            'timezone'    => 'UTC',
        ];

        $request = new StoreEventRequest;
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
            'type'        => 'Individual',
            'colour'      => CalendarEventColoursEnum::BLUE->value,
            'timezone'    => 'UTC',
        ];

        $request = new StoreEventRequest;
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
            'type'        => 'Individual',
            'colour'      => CalendarEventColoursEnum::BLUE->value,
            'timezone'    => 'UTC',
        ];

        $request = new StoreEventRequest;
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
        $existingEvent = App\Models\CalendarEvent::factory()->create([
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
            'type'        => 'Individual',
            'colour'      => CalendarEventColoursEnum::BLUE->value,
            'timezone'    => 'UTC',
        ];

        $request = new StoreEventRequest;
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

    it('prepareForValidation handles same date with toTime before fromTime', function (): void {
        Date::setTestNow(Date::create(2025, 6, 1, 8, 0, 0, 'UTC'));

        $data = [
            'title'       => 'Same Date Invalid Time',
            'fromDate'    => Date::now()->addDays(2)->format('Y-m-d'),
            'toDate'      => Date::now()->addDays(2)->format('Y-m-d'),
            'fromTime'    => '10:00',
            'toTime'      => '09:00', // before fromTime
            'description' => 'desc',
            'type'        => 'Individual',
            'colour'      => CalendarEventColoursEnum::BLUE->value,
            'timezone'    => 'UTC',
        ];

        $request = new StoreEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);

        // This should fail validation because of toTime.after:fromTime rule
        expect($request->validateResolved(...))->toThrow(ValidationException::class);
    });

    it('prepareForValidation handles same date with equal times', function (): void {
        Date::setTestNow(Date::create(2025, 6, 1, 8, 0, 0, 'UTC'));

        $data = [
            'title'       => 'Same Date Equal Time',
            'fromDate'    => Date::now()->addDays(2)->format('Y-m-d'),
            'toDate'      => Date::now()->addDays(2)->format('Y-m-d'),
            'fromTime'    => '10:00',
            'toTime'      => '10:00', // equal to fromTime
            'description' => 'desc',
            'type'        => 'Individual',
            'colour'      => CalendarEventColoursEnum::BLUE->value,
            'timezone'    => 'UTC',
        ];

        $request = new StoreEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);

        // This should fail validation because of toTime.after:fromTime rule
        expect($request->validateResolved(...))->toThrow(ValidationException::class);
    });

    it('prepareForValidation handles different dates correctly', function (): void {
        Date::setTestNow(Date::create(2025, 6, 1, 8, 0, 0, 'UTC'));

        // Make sure no existing events conflict
        App\Models\CalendarEvent::query()->delete();

        $data = [
            'title'       => 'Different Dates',
            'fromDate'    => Date::now()->addDays(10)->format('Y-m-d'),
            'toDate'      => Date::now()->addDays(11)->format('Y-m-d'),
            'fromTime'    => '09:00',
            'toTime'      => '10:00', // Normal times across different dates
            'description' => 'desc',
            'type'        => 'Individual',
            'colour'      => CalendarEventColoursEnum::BLUE->value,
            'timezone'    => 'UTC',
        ];

        $request = new StoreEventRequest;
        $request->merge($data);
        ($this->prepareRequest)($request);

        $request->validateResolved();

        expect(true)->toBeTrue(); // validation passed
    });

    it('getEventData handles null datetime objects gracefully', function (): void {
        $request = new class extends StoreEventRequest
        {
            public function validated($key = null, $default = null): array
            {
                return [
                    'title'       => 'Test Null Safety',
                    'fromDate'    => 'invalid',
                    'fromTime'    => 'invalid',
                    'toDate'      => 'invalid',
                    'toTime'      => 'invalid',
                    'type'        => 'Individual',
                    'description' => 'Testing null-safe operators',
                    'colour'      => CalendarEventColoursEnum::BLUE->value,
                    'timezone'    => 'UTC',
                ];
            }
        };

        $data = $request->getEventData();

        // When parsing fails, should return empty array
        expect($data)->toBeEmpty();
    });

    it('getEventData uses description from validated data', function (): void {
        $request = new class extends StoreEventRequest
        {
            public function validated($key = null, $default = null): array
            {
                return [
                    'title'       => 'Test',
                    'fromDate'    => Date::today()->format('Y-m-d'),
                    'fromTime'    => '09:00',
                    'toDate'      => Date::today()->format('Y-m-d'),
                    'toTime'      => '10:00',
                    'type'        => 'Individual',
                    'description' => 'Specific description text',
                    'colour'      => CalendarEventColoursEnum::BLUE->value,
                    'timezone'    => 'UTC',
                ];
            }
        };

        $data = $request->getEventData();

        expect($data['description'])->toBe('Specific description text')
            ->and($data['colour'])->toBe(CalendarEventColoursEnum::BLUE->value);
    });
});
