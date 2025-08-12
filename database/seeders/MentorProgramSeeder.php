<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\Currency;
use App\Models\MentorProfile;
use App\Models\MentorProgram;
use App\Models\MentorTag;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class MentorProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = Currency::query()->pluck('id');

        collect()->times(20, function (int $index) use ($currencies) {
            $mentor = User::factory()->create([
                'email'    => "mentor{$index}@example.com",
                'username' => "Mentor {$index}",
            ]);

            $mentor->assignRole(Role::findByName(RoleEnum::MENTOR->value));

            UserProfile::factory()
                ->recycle($mentor)
                ->create([
                    'user_id' => $mentor->id,
                ]);

            MentorProfile::factory()
                ->recycle($mentor)
                ->has(MentorTag::factory()->count(fake()->numberBetween(1, 3)), 'mentorTags')
                ->has(MentorProgram::factory()->count(fake()->numberBetween(2, 5)), 'mentorPrograms')
                ->create([
                    'user_id' => $mentor->id,
                ]);
        });

    }
}
