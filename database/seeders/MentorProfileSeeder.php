<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\MentorProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class MentorProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()
            ->role(RoleEnum::MENTOR->value)
            ->whereDoesntHave('mentorProfile')
            ->each(function (User $mentor): void {
                MentorProfile::factory()->create(['user_id' => $mentor->getKey()]);
            });
    }
}
