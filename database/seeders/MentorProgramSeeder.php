<?php

namespace Database\Seeders;

use App\Models\User;
use App\Enums\RoleEnum;
use App\Enums\RoleGuardEnum;
use App\Models\Currency;
use App\Models\MentorProgram;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class MentorProgramSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mentor = User::factory()->create(['username' => 'Test Mentor']);
        $mentor->assignRole(Role::findByName(RoleEnum::MENTOR->value, RoleGuardEnum::MENTOR->value));
        $currencies = Currency::pluck('id');

        collect()->times(10, function () use ($mentor, $currencies) {
            MentorProgram::factory()->create([
                'mentor_id' => $mentor->id,
                'currency_id' => $currencies->random(),
            ]);
        });
        
    }
}
