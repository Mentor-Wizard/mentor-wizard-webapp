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
            'start_time'   => fake()->dateTimeBetween('06:00', '12:00')->format('H:i'),
            'end_time'     => fake()->dateTimeBetween('14:00', '22:00')->format('H:i'),
            'type'         => UserScheduleRecordType::WORKING_DAY->value,
        ];
    }
}
