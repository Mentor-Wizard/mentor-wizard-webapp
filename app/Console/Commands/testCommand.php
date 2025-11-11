<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Http\Resources\EventMonthViewResource;
use App\Models\CalendarEvent;
use App\Models\User;
use App\Services\Calendar\CheckTimeSlotReservedService;
use Gate;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class testCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-command';

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

        $todayDate = Carbon::parse('2025-11-11', 'Europe/Kyiv');
        $yesterday = Carbon::parse('2025-11-10', 'Europe/Kyiv');
        dd($todayDate->isSameDay($yesterday));

        $startDate = Carbon::parse('2025-11-11', 'Europe/Kyiv')->startOfWeek();
        $startUTCDate = $startDate->setTimezone('UTC');
        dd($startUTCDate);
        //
        //        $user =User::with('calendarEvents')->find(71);
        //        $startDate = Carbon::now()->startOfMonth();
        //            $endDate = Carbon::now()->endOfMonth();
        //        $events = $user->calendarEvents()
        //            ->whereBetween('start_date_time', [$startDate, $endDate])
        //            ->orderBy('start_date_time')
        //            ->get()
        //            ->groupBy('date')
        //            ->map(fn (Collection $dateEvents): array => $this->formatDateEvents($dateEvents))
        //            ->toArray();
        //        dd($events);

        //            $startDateTime = Carbon::createFromFormat(
        //                'Y-m-d H:i',
        //                '2025-10-31'.' '.'09:00',
        //                $timezone
        //            )?->setTimezone('UTC');
        //        $startDateTime = Carbon::createFromFormat(
        //            'Y-m-d H:i',
        //            '2025-10-31'.' '.'09:00',$timezone
        //        );
        //            $endDateTime = Carbon::createFromFormat(
        //                'Y-m-d H:i',
        //                '2025-10-31'.' '.'10:00',
        //                $timezone
        //            )?->setTimezone('UTC');
        //
        //            dd($startDateTime);
        //            dd($startDateTime, $endDateTime);

        $fromDate = '2025-10-31';
        $fromTime = '09:00';
        $toDate = '2025-10-31';
        $toTime = '10:00';
        $user = User::with('calendarEvents')->find(71);
        $timezone = 'Europe/Kyiv';

        $isWithinAvailableSlots = new CheckTimeSlotReservedService(
            $fromDate,
            $fromTime,
            $toDate,
            $toTime,
            $timezone, $user)->execute();

        dd($isWithinAvailableSlots);
        $user = User::query()->find(71);
        dd($user->can('create', CalendarEvent::class));

        $this->authorize('create', CalendarEvent::class);
        $response = Gate::inspect('create', $user);

        dd($response);

        if ($response->allowed()) {
            // The action is authorized...
        } else {
            echo $response->message();
        }

        dd($user->can('create'));
        dd($user->hasRole('mentor'));

    }

    private function formatDateEvents(Collection $dateEvents): array
    {
        /** @var ?CalendarEvent $firstEvent */
        $firstEvent = $dateEvents->first();
        $payload = [
            'date'   => $firstEvent->start_date_time->setTimezone('Europe/Kyiv')->format('Y-m-d'),
            'events' => EventMonthViewResource::collection($dateEvents)
                ->additional(['timezone' => 'Europe/Kyiv'])->resolve(),
        ];

        $parsedDate = Carbon::parse('2025-10-24', 'Europe/Kyiv');
        $eventDate = $firstEvent->start_date_time;

        if ($parsedDate->isSameMonth($eventDate)) {
            $payload['isCurrentMonth'] = true;
        }

        if ($parsedDate->isSameDay($eventDate)) {
            $payload['isSelected'] = true;
        }

        if (Carbon::now()->isSameDay($eventDate)) {
            $payload['isToday'] = true;
        }

        return $payload;
    }
}
