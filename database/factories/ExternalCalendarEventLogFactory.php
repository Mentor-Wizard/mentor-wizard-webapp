<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CalendarProviderEnum;
use App\Enums\ExternalCalendarEventLogTypeEnum;
use App\Models\CalendarEvent;
use App\Models\ExternalCalendarEvent;
use App\Models\ExternalCalendarEventLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/**
 * @extends Factory<ExternalCalendarEventLog>
 */
class ExternalCalendarEventLogFactory extends Factory
{
    #[Override]
    protected $model = ExternalCalendarEventLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'external_calendar_event_id' => ExternalCalendarEvent::factory(),
            'calendar_event_id'          => CalendarEvent::factory(),
            'user_id'                    => User::factory(),
            'provider'                   => fake()->randomElement(CalendarProviderEnum::cases()),
            'type'                       => fake()->randomElement(ExternalCalendarEventLogTypeEnum::cases()),
            'message'                    => fake()->sentence(),
        ];
    }

    public function error(): static
    {
        return $this->state(['type' => ExternalCalendarEventLogTypeEnum::Error]);
    }

    public function success(): static
    {
        return $this->state(['type' => ExternalCalendarEventLogTypeEnum::Success]);
    }

    public function info(): static
    {
        return $this->state(['type' => ExternalCalendarEventLogTypeEnum::Info]);
    }
}
