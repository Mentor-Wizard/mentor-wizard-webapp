<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            AdminSeeder::class,
            MentiSeeder::class,
            SuperAdminSeeder::class,
            CoachSeeder::class,
            CurrencySeeder::class,
            MentorProgramSeeder::class,
            EventSeeder::class,
        ]);
    }
}
