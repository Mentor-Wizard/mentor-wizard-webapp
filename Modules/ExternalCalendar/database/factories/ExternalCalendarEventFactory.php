<?php

declare(strict_types=1);

namespace Modules\ExternalCalendar\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Calendar\Models\CalendarEvent;
use Modules\ExternalCalendar\Enums\CalendarProviderEnum;
use Modules\ExternalCalendar\Models\ExternalCalendarEvent;
use Override;

/**
 * @extends Factory<ExternalCalendarEvent>
 */
class ExternalCalendarEventFactory extends Factory
{
    #[Override]
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
        return $this->state(['provider' => CalendarProviderEnum::GOOGLE]);
    }

    public function outlook(): static
    {
        return $this->state(['provider' => CalendarProviderEnum::OUTLOOK]);
    }

    public function apple(): static
    {
        return $this->state(['provider' => CalendarProviderEnum::APPLE]);
    }
}
