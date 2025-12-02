<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

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
            MentorProgramSeeder::class,
            MentorReviewSeeder::class,
            ChatMessageSeeder::class,
        ]);
    }
}
