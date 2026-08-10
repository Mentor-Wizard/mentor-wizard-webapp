<?php

declare(strict_types=1);

namespace Modules\ExternalCalendar\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Calendar\Models\CalendarEvent;
use Modules\ExternalCalendar\Enums\CalendarProviderEnum;
use Modules\ExternalCalendar\Enums\ExternalCalendarEventLogTypeEnum;
use Modules\ExternalCalendar\Models\ExternalCalendarEvent;
use Modules\ExternalCalendar\Models\ExternalCalendarEventLog;
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
