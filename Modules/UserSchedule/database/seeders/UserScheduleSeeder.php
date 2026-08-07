<?php

declare(strict_types=1);

namespace Modules\UserSchedule\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\UserSchedule\Enums\UserScheduleRecordType;
use Modules\UserSchedule\Models\UserSchedule;

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
                    'type'        => UserScheduleRecordType::WORKING_DAY,
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
