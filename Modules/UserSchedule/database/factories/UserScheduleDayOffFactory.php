<?php

declare(strict_types=1);

namespace Modules\UserSchedule\Database\Factories;

use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;
use Modules\UserSchedule\Enums\UserScheduleRecordType;
use Modules\UserSchedule\Models\UserSchedule;
use Override;

/**
 * @extends Factory<UserSchedule>
 */
class UserScheduleDayOffFactory extends Factory
{
    #[Override]
    protected $model = UserSchedule::class;

    /**
     * @return array<string, UserFactory|CarbonImmutable|int|string>
     */
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
