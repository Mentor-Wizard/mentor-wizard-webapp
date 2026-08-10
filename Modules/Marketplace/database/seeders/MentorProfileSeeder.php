<?php

declare(strict_types=1);

namespace Modules\Marketplace\Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\Marketplace\Models\MentorProfile;

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
