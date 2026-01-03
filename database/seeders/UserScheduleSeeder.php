<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\UserSchedule;
use Illuminate\Database\Seeder;

class UserScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        UserSchedule::factory()
            ->count(10)
            ->create();
    }
}
