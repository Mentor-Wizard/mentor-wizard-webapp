<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CalendarProviderEnum;
use App\Models\CalendarEvent;
use App\Models\ExternalCalendarEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalCalendarEvent>
 */
class ExternalCalendarEventFactory extends Factory
{
    protected $model = ExternalCalendarEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'calendar_event_id' => CalendarEvent::factory(),
            'user_id'           => User::factory(),
            'provider'          => fake()->randomElement(CalendarProviderEnum::cases()),
            'external_event_id' => fake()->uuid(),
        ];
    }

    public function google(): static
    {
        return $this->state(['provider' => CalendarProviderEnum::Google]);
    }

    public function outlook(): static
    {
        return $this->state(['provider' => CalendarProviderEnum::Outlook]);
    }

    public function apple(): static
    {
        return $this->state(['provider' => CalendarProviderEnum::Apple]);
    }
}
