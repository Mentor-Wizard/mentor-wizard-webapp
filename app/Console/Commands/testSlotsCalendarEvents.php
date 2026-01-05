<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\MentorProgram;
use App\Models\User;
use App\Services\Calendar\GetBookingCalendarEventsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;

class testSlotsCalendarEvents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-slots-calendar-events';

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
        $date = Date::now();
        $user = User::query()->where('id', 13)->first();
        $mentorProgram = MentorProgram::query()->where('id', 2)->first();
        $availableSlots = new GetBookingCalendarEventsService($date, $user, 'Europe/Kyiv', true, $mentorProgram);
        dd($availableSlots->getFormattedMonthAvailableSlots());

    }
}
