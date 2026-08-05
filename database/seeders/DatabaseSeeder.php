<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Chat\Database\Seeders\ChatDatabaseSeeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            CurrencySeeder::class,
            UserSeeder::class,
            AdminSeeder::class,
            MentiSeeder::class,
            SuperAdminSeeder::class,
            CoachSeeder::class,
            MentorTagSeeder::class,
            MentorProfileSeeder::class,
            MentorProgramSeeder::class,
            CalendarEventSeeder::class,
            MentorReviewSeeder::class,
            ChatDatabaseSeeder::class,
        ]);
    }
}
