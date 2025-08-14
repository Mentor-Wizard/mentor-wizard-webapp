<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EventStatusEnum;
use App\Enums\EventTypeEnum;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class EventFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password = null;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dateTime = fake()->dateTimeBetween('-5 month', '+5 month');
        $duration = fake()->randomNumber(5, true) * 1800;
        $endTime = Carbon::parse($dateTime)->addSeconds($duration);
        $date = $dateTime->format('Y-m-d');

        return [
            'unique_id'         => fake()->uuid(),
            'title'             => fake()->sentence(3),
            'status'            => fake()->randomElement(EventStatusEnum::values()),
            'start_date_time'   => $dateTime,
            'end_date_time'     => $endTime,
            'date'              => $date,
            'duration'          => $duration,
            'type'              => fake()->randomElement(EventTypeEnum::values()),
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
