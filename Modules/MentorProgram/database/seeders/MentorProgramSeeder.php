<?php

declare(strict_types=1);

namespace Modules\MentorProgram\Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\Currency;
use App\Models\MentorProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\MentorProgram\Models\MentorProgram;
use Modules\MentorProgram\Models\MentorProgramBlock;
use Spatie\Permission\Models\Role;

class MentorProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()
            ->count(10)
            ->create()
            ->each(function ($mentor): void {
                $mentor->assignRole(Role::findByName(RoleEnum::MENTOR->value));
                MentorProfile::factory()->create(['user_id' => $mentor->id]);

                $currencies = Currency::query()->pluck('id');

                collect()->times(10, function () use ($mentor, $currencies): void {
                    $program = MentorProgram::factory()->create([
                        'mentor_id'   => $mentor->id,
                        'currency_id' => $currencies->random(),
                    ]);
                    MentorProgramBlock::factory()
                        ->count(5)
                        ->create([
                            'mentor_program_id' => $program->id,
                        ]);
                });
            });
    }
}
