<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Enums\TagEnum;
use App\Models\Currency;
use App\Models\MentorProfile;
use App\Models\MentorProgram;
use App\Models\MentorProgramBlock;
use App\Models\MentorTag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class MentorProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stackTagIds = MentorTag::query()->where('type', TagEnum::STACK)->pluck('id');
        $languageTagIds = MentorTag::query()->where('type', TagEnum::LANGUAGE)->pluck('id');

        User::factory()
            ->count(10)
            ->create()
            ->each(function ($mentor) use ($stackTagIds, $languageTagIds): void {
                $mentor->assignRole(Role::findByName(RoleEnum::MENTOR->value));
                $profile = MentorProfile::factory()->create(['user_id' => $mentor->getKey()]);

                $profile->mentorTags()->attach(
                    $stackTagIds->random(random_int(5, 15)),
                );
                $profile->mentorTags()->attach(
                    $languageTagIds->random(random_int(2, 5)),
                );

                $currencies = Currency::query()->pluck('id');

                collect()->times(10, function () use ($mentor, $currencies): void {
                    $program = MentorProgram::factory()->create([
                        'mentor_id'   => $mentor->getKey(),
                        'currency_id' => $currencies->random(),
                    ]);
                    MentorProgramBlock::factory()
                        ->count(5)
                        ->create([
                            'mentor_program_id' => $program->getKey(),
                        ]);
                });
            });
    }
}
