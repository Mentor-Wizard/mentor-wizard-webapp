<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RoleEnum;
use App\Enums\UserScheduleRecordType;
use App\Models\User;
use App\Models\UserSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserSchedule>
 */
class UserScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::MENTOR->value);

        return [
            'user_id'      => $user,
            'day_of_week'  => fake()->numberBetween(0, 6),
            'start_time'   => '09:00',
            'end_time'     => '17:00',
            'type'         => UserScheduleRecordType::WORKING_DAY->value,
        ];
    }
}
