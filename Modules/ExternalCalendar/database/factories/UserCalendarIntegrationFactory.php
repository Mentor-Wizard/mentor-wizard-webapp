<?php

declare(strict_types=1);

namespace Modules\ExternalCalendar\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\ExternalCalendar\Enums\CalendarProviderEnum;
use Modules\ExternalCalendar\Enums\CalendarSyncStatusEnum;
use Modules\ExternalCalendar\Models\UserCalendarIntegration;
use Override;

/**
 * @extends Factory<UserCalendarIntegration>
 */
class UserCalendarIntegrationFactory extends Factory
{
    #[Override]
    protected $model = UserCalendarIntegration::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'            => User::factory(),
            'provider'           => fake()->randomElement(CalendarProviderEnum::cases()),
            'access_token'       => fake()->sha256(),
            'refresh_token'      => fake()->sha256(),
            'token_expires_at'   => now()->addHour(),
            'needs_reauth'       => false,
            'sync_status'        => CalendarSyncStatusEnum::ACTIVE,
            'last_error_message' => null,
            'last_synced_at'     => null,
        ];
    }

    public function google(): static
    {
        return $this->state(['provider' => CalendarProviderEnum::GOOGLE]);
    }

    public function disconnected(): static
    {
        return $this->state([
            'sync_status'  => CalendarSyncStatusEnum::DISCONNECTED,
            'access_token' => '',
        ]);
    }

    public function needsReauth(): static
    {
        return $this->state([
            'needs_reauth' => true,
            'sync_status'  => CalendarSyncStatusEnum::ERROR,
        ]);
    }
}
