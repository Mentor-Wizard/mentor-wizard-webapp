<?php

declare(strict_types=1);

namespace Modules\UserSchedule\Database\Factories;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\UserSchedule\Enums\UserScheduleRecordType;
use Modules\UserSchedule\Models\UserSchedule;
use Override;

/**
 * @extends Factory<UserSchedule>
 */
class UserScheduleFactory extends Factory
{
    #[Override]
    protected $model = UserSchedule::class;

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
