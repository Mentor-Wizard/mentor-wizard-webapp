<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Calendar\Services\CheckAvailableSlots;
use App\Actions\Calendar\Services\GetAvailableSlots;
use App\Actions\Calendar\Services\GetMonthEvents;
use App\Actions\Calendar\Services\GetWeeklyEvents;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class testCalendarEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-calendar-events';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): never
    {
        $user = User::query()->find(436);
        //        $events = new GetMonthEvents($user, '2025-09-05','Europe/Kyiv')->execute();
        //        $events = new GetWeeklyEvents($user, '2025-09-05','Europe/Kyiv')->execute();
        $timezone = 'Europe/Kyiv';
        $fromDate = '2025-09-13';
        $fromTime = '09:00';
        $toDate = '2025-09-13';
        $toTime = '10:00';

        $slots = new GetAvailableSlots($user, $timezone)->execute();
        $startDateTimestamp = Carbon::createFromFormat(
            'Y-m-d H:i',
            $fromDate.' '.$fromTime, $timezone)->timestamp;
        $endDateTimestamp = Carbon::createFromFormat(
            'Y-m-d H:i',
            $toDate.' '.$toTime,
            $timezone
        )->timestamp;

        $isAvailable = new CheckAvailableSlots($slots, $startDateTimestamp, $endDateTimestamp)->execute();
        dd($isAvailable);

    }
}
