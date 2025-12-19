<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UserScheduleRecordType;
use App\Models\User;
use App\Models\UserSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;

/**
 * @extends Factory<UserSchedule>
 */
class UserScheduleDayOffFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = UserSchedule::class;

    public function definition(): array
    {
        return [
            'user_id'      => User::factory(),
            'day_of_week'  => 1,
            'start_time'   => '00:00',
            'end_time'     => '24:00',
            'day_off_date' => Date::today()->addDays(fake()->randomNumber(2)),
            'type'         => UserScheduleRecordType::DAY_OFF->value,
        ];
    }
}
