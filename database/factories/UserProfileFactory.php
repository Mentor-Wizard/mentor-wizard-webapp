<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserProfile>
 */
class UserProfileFactory extends Factory
{
    protected $model = UserProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->firstName,
            'last_name' => fake()->lastName,
            'linkedin' => fake()->url,
            'telegram' => fake()->userName,
            'whatsapp' => fake()->phoneNumber,
            'phone' => fake()->phoneNumber,
            'description' => fake()->text(),
        ];
    }
}
