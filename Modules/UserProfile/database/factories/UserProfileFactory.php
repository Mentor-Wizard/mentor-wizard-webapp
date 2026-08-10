<?php

declare(strict_types=1);

namespace Modules\UserProfile\Database\Factories;

use App\Models\Currency;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\UserProfile\Models\UserProfile;
use Override;

/**
 * @extends Factory<UserProfile>
 */
class UserProfileFactory extends Factory
{
    #[Override]
    protected $model = UserProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'        => User::factory(),
            'name'           => fake()->firstName,
            'last_name'      => fake()->lastName,
            'linkedin'       => fake()->url,
            'telegram'       => fake()->userName,
            'whatsapp'       => fake()->phoneNumber,
            'phone'          => fake()->phoneNumber,
            'cost_per_hour'  => fake()->randomFloat(2, 10, 100),
            'currency_id'    => Currency::query()->inRandomOrder()->value('id') ?? Currency::factory(),
            'timezone'       => fake()->timezone,
        ];
    }
}
