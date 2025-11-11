<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Calendar\GetWeeklyEventsService;
use Illuminate\Console\Command;

class testWeekService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-week-service';

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
        $user = User::query()->find(71);
        $weekCalendar = new GetWeeklyEventsService($user, '2025-10-27', 'Europe/Kyiv')->execute();
        dd($weekCalendar);

        dd($user);
        //         new GetWeeklyEventsService()
    }

    public function getWeek() {}
}
