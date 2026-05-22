<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CalendarProviderEnum;
use App\Enums\CalendarSyncStatusEnum;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserCalendarIntegration>
 */
class UserCalendarIntegrationFactory extends Factory
{
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
