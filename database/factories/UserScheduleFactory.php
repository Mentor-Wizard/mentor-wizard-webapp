<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserScheduleRecordType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserSchedule>
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
        return [
            'user_id'      => User::factory(),
            'day_of_week'  => fake()->numberBetween(0, 6),
            'start_time'   => '09:00',
            'end_time'     => '17:00',
            'type'         => fake()->randomElement(UserScheduleRecordType::values()),
            'timezone'     => 'UTC',
        ];
    }
}
