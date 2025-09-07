<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Http\Resources\EventWeekViewResource;
use App\Models\Event;
use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class testTimezone extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-timezone';

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
        $startUtc = Carbon::create(2025, 8, 24, 22, 0, 0, 'UTC');
        $endUtc = (clone $startUtc)->addHour();

        $event = Event::factory()->create([
            'title'           => 'TZ Week',
            'start_date_time' => $startUtc,
            'end_date_time'   => $endUtc,
            'duration'        => $startUtc?->diffInSeconds($endUtc),
            'date'            => $startUtc?->format('Y-m-d'),
            'type'            => 'individual',
            'web_link'        => 'https://example.com/tz-week',
            'description'     => 'tz',
        ]);

        $resource = new EventWeekViewResource($event, 'Europe/Kyiv');
        $array = $resource->toArray(request());
        dd($array);

        //
        //        $user = User::find(358);
        //        $startDate = Carbon::parse('2025-05-24 21:03:01', 'Europe/Kyiv');
        //        $startDateUTC = Carbon::parse('2025-05-24 21:03:01' );
        //        dd($startDate,$startDateUTC);

        // //        $endDate = Carbon::parse('2025-05-24 21:03:25', 'Europe/Kyiv');
        //        $endDate = Carbon::parse('2025-05-24 21:03:25');
        //        $foundEvent = $user->events()
        //            ->whereBetween('start_date_time', [$startDate, $endDate])
        //            ->orderBy('start_date_time')
        //            ->get();
        //
        //        dd($foundEvent);
        //
        //
        //        dd(Event::query()->with('users')->get()->first()->users);

        //        2025-05-24 21:03:15"

        //        $startDate = Carbon::now("Europe/Kyiv")->startOfWeek();
        // //        $startDate = Carbon::now()->startOfWeek();
        // //        $endDate = Carbon::now("Europe/Kyiv")->endOfWeek();
        //        $endDate = Carbon::now()->endOfWeek();
        //        $weekDays = CarbonPeriod::create($startDate, '1 day', $endDate);
        //
        //        dd($weekDays->toArray());
        //        dd($weekDays);

    }
}
