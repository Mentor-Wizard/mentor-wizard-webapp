<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Calendar\StoreCalendarPage;
use App\Enums\EventStatusEnum;
use App\Enums\EventTypeEnum;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\MentorProgram;
use App\Models\User;
use Carbon\CarbonInterval;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;
use Str;

class test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {

        $event = Event::factory()->create();
        $user = User::factory()->create();

        $relation = $event->users();
        expect($relation)->toBeInstanceOf(BelongsToMany::class);

        $event->users()->attach($user->id, ['role' => 'host', 'created_at' => now(), 'updated_at' => now()]);

        $pivot = $event->users()->where('users.id', $user->id)->first()->pivot;

        dd($pivot);

        //
        //        $user = User::query()->find(55);
        //        //        $slots =$user->getAvailableSlots();
        //        //        $events =$user->getMonthFormattedEvents('2025-08-14');
        //        //        $events =$user->getWeekFormattedEvents('2025-08-14');
        //        $events = $user->getDailyFormattedEvents('2025-08-14');
        //        dd($events);
        //        //        $interaval = CarbonInterval::seconds(2342)->cascade()->format('%H:%I');
        //
        //        $interaval = CarbonInterval::seconds(2342)->format('%H:%I')->forHumans();
        //        dd($interaval);

        //        $user = User::find(58);
        //        $user->assignRole('mentor');
        //
        //        dd($user);

        //        $startDate = Carbon::parse('2025-07-01');
        //        $endDate = Carbon::parse('2025-07-15');
        //
        // // Create a period with daily intervals
        //        $period = CarbonPeriod::create($startDate, '1 day', $endDate);
        //
        // // Convert to array or collection
        //        $dates = $period->toArray();
        //        dd($dates);
        //

        //        $timezone = "Europe/Kiev";
        //        $startCalendarMonth =  Carbon::parse("2025-03-11",  "Europe/Kiev")->startOfMonth();
        //        $endCalendarMonth = Carbon::parse("2026-01-03",  "Europe/Kiev")->endOfMonth();
        //
        //        $todayDate = Carbon::parse("2025-08-08",  "Europe/Kiev");
        //        $calendarView = [];
        //        $monthsPeriod = CarbonPeriod::create($startCalendarMonth, '1 month', $endCalendarMonth);
        //
        //        foreach ($monthsPeriod as $month) {
        //            $startDate = Carbon::parse($month, $timezone ?? "Europe/Kiev")->startOfMonth()->startOfWeek();
        //            $endDate = Carbon::parse($month, $timezone ?? "Europe/Kiev")->endOfMonth()->endOfWeek();
        //            $daysPeriod = CarbonPeriod::create($startDate, '1 day', $endDate);
        //            $monthDates = $daysPeriod->toArray();
        //
        //            foreach ($monthDates as $monthDate) {
        //                $payload = ['date' => $monthDate->format('Y-m-d')];
        //                if (Carbon::parse($monthDate)->isSameMonth($todayDate)) {
        //                    $payload['isCurrentMonth'] = true;
        //                };
        //                if (Carbon::parse($monthDate)->isSameDay($todayDate)) {
        //                    $payload['isSelected'] = true;
        //                }
        //                if (Carbon::now()->isSameDay(Carbon::parse($monthDate))) {
        //                    $payload['isToday'] = true;
        //                }
        //                if(isset($calendarView[$month->format('Y-m')])){
        //                    $calendarView[$month->format('Y-m')][] = $payload;
        //                }else{
        //                    $calendarView[$month->format('Y-m')] = [$payload];
        //                }
        //            }
        //        }
        //
        //        dd($calendarView);

        //        $startDate = Carbon::parse("2026-01-01",  "Europe/Kiev")->startOfMonth()->startOfWeek();
        //        $endDate = Carbon::parse("2026-01-01",  "Europe/Kiev")->endOfMonth()->endOfWeek();
        //        $daysPeriod = CarbonPeriod::create($startDate, '1 day', $endDate);
        //        $monthDates = $daysPeriod->toArray();
        //        dd($monthDates);

        //
        //        $storeAction = new StoreCalendarPage()->handle()
        $uniqueId = Str::uuid();
        $user = User::query()->find(5);
        $eventData = [
            'unique_id'         => $uniqueId,
            'title'             => 'Default event',
            'status'            => EventStatusEnum::CONFIRMED,
            'start_date_time'   => Carbon::parse('2025-08-08T12:00:00'),
            'duration'          => 3600,
            'type'              => EventTypeEnum::INDIVIDUAL,
            'web_link'          => 'https://example.com',
            'description'       => 'This is a description for the new event.',
            'mentor_program_id' => MentorProgram::query()->inRandomOrder()->first()?->getKey(),
        ];

        $response = post(route('pages.calendar.store'), $eventData);

        dd($response);

        $timezone = 'Europe/Kiev';
        $date = '2025-08-08';
        $user = User::query()->find(5);

        $startDate = Carbon::parse($date, $timezone ?? 'Europe/Kiev');
        dd($startDate);
        $monthEvents = $user->getMonthFormattedEvents($date, $timezone);
        dd($monthEvents);

        $userEventsCheck = $user->events();
        $startDate = Carbon::parse($date, $timezone ?? 'Europe/Kiev')->startOfMonth()->startOfWeek();
        $endDate = Carbon::parse($date, $timezone ?? 'Europe/Kiev')->endOfMonth()->endOfWeek();
        $hasEventsBefore = false;
        $hasEventsAfter = false;
        $userEventsCheckBefore = clone $userEventsCheck;
        $userEventsCheckAfter = clone $userEventsCheck;

        //        dd($endDate);
        $userEventsCheckBefore = $userEventsCheckBefore->where('start_date_time', '<', $startDate)->count();
        $userEventsCheckAfter = $userEventsCheckAfter->where('start_date_time', '>', $endDate)->count();
        dD($userEventsCheckAfter);

        if ($userEventsCheckAfter->where('start_date_time', '>', $endDate->endOfDay())->count() > 0) {
            $hasEventsAfter = true;
        }

        if ($userEventsCheckBefore->where('start_date_time', '<', $startDate)->count() > 0) {
            $hasEventsBefore = true;
        }

        dd($hasEventsBefore, $hasEventsAfter);

        dd($user->getDailyFormattedEvents($date, $timezone));

        $firstEvent = $user->events()->first();
        $latestEvent = $user->events()->latest()->first();

        dd($firstEvent->start_date_time->format('Y-m-d'), $latestEvent->start_date_time->format('Y-m-d'));
        //        $firstEvent = $events->first();
        //        $latestEvent = $events->latest()->first();
        $startDate = Carbon::parse($firstEvent->start_date_time ?? $date, $timezone ?? 'Europe/Kiev')->startOfMonth()->startOfWeek();
        $endDate = Carbon::parse($latestEvent->start_date_time ?? $date, $timezone ?? 'Europe/Kiev')->endOfMonth()->endOfWeek();

        $period = CarbonPeriod::create($startDate, '1 day', $endDate);
        dd($period->toArray());

        $date = '2025-07-29';
        //
        User::query()->find(5)->assignRole('mentor');
        $eventsFormatted = User::query()->find(5)->getWeekFormattedEvents($date);
        dd($eventsFormatted);

        $events = User::query()->find(31)->events()
            ->whereBetween('start_date_time',
                [Carbon::parse($date, $timezone ?? 'Europe/Kiev')->startOfMonth()->startOfWeek(),
                    Carbon::parse($date, $timezone ?? 'Europe/Kiev')->endOfMonth()->endOfWeek()])->get();

        $dayEvents = $events->groupBy(fn (Event $event): string => Carbon::parse($event->start_date_time)->format('Y-m-d'))->map(fn (Collection $dateEvents): array
            //                $events[] = $dateEvents->map(function (Event $event) use (&$formattedEvents, $date) {
            //                    return $event;
            //                });
            //                $events = EventResource::collection($dateEvents);
            => [
                'date'           => $dateEvents->first()->start_date_time->format('Y-m-d'),
                'events'         => EventResource::collection($dateEvents)->resolve(),
                'isCurrentMonth' => Carbon::parse($date)->isSameMonth($dateEvents->first()->start_date_time),
                'isToday'        => Carbon::parse($date)->isSameDay($dateEvents->first()->start_date_time),
            ]);
        dd($dayEvents);

    }
}
