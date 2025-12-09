<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserScheduleRecordType;
use App\Models\User;
use App\Models\UserSchedule;
use Illuminate\Database\Seeder;

class UserScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some users to create schedules for
        $users = User::query()->limit(5)->get();

        foreach ($users as $user) {
            // Create working hours for weekdays (Monday to Friday)
            for ($dayOfWeek = 1; $dayOfWeek <= 5; $dayOfWeek++) {
                UserSchedule::query()->create([
                    'user_id'     => $user->getKey(),
                    'day_of_week' => $dayOfWeek,
                    'start_time'  => '09:00:00',
                    'end_time'    => '17:00:00',
                    'type'        => UserScheduleRecordType::ALL_WORKING_DAYS,
                    'timezone'    => 'UTC',
                ]);
            }

            // Create a day off example for the first user
            if ($user->getKey() === $users->first()->getKey()) {
                UserSchedule::query()->create([
                    'user_id'      => $user->getKey(),
                    'day_off_date' => now()->addDays(10),
                    'type'         => UserScheduleRecordType::DAY_OFF,
                    'timezone'     => 'UTC',
                ]);
            }
        }

        // Create additional random schedules using factory
        UserSchedule::factory()->count(20)->create();
    }
}
