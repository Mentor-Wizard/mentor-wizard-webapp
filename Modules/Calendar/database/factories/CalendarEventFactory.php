<?php

declare(strict_types=1);

namespace Modules\Calendar\Database\Factories;

use App\Enums\MentorSessionTypeEnum;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;
use Modules\Calendar\Enums\CalendarEventStatusEnum;
use Modules\Calendar\Enums\CalendarEventTypeEnum;
use Modules\Calendar\Models\CalendarEvent;

/**
 * @extends Factory<CalendarEvent>
 */
class CalendarEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dateTime = fake()->dateTimeBetween('-2 month', '+5 month');
        $durationInMinutes = fake()->randomElement([30, 45, 60, 90, 120]);
        $endTime = Date::parse($dateTime)->addMinutes($durationInMinutes);
        $date = $dateTime->format('Y-m-d');

        return [
            'title'             => fake()->sentence(3),
            'status'            => fake()->randomElement(CalendarEventStatusEnum::values()),
            'start_date_time'   => $dateTime,
            'end_date_time'     => $endTime,
            'date'              => $date,
            'type'              => fake()->randomElement(CalendarEventTypeEnum::values()),
            'session_type'      => fake()->randomElement(MentorSessionTypeEnum::values()),
            'web_link'          => fake()->url(),
            'description'       => fake()->text(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }
}
