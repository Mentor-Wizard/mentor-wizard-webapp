<?php

declare(strict_types=1);

use App\Enums\CalendarEventColoursEnum;
use App\Http\Requests\Calendar\StoreEventRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Routing\Redirector;

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
        Illuminate\Support\Facades\Date::setTestNow(Illuminate\Support\Facades\Date::create(2025, 6, 1, 8, 0, 0, 'UTC'));

        $data = [
            'title'       => 'Payload Build',
            'fromDate'    => Illuminate\Support\Facades\Date::now()->addDays(2)->format('Y-m-d'),
            'toDate'      => Illuminate\Support\Facades\Date::now()->addDays(2)->format('Y-m-d'),
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
            ->and($payload['date'])->toBe(Illuminate\Support\Facades\Date::now()->addDays(2)->format('Y-m-d'))
            ->and($payload['duration'])->toBe(90 * 60);
    });
});
