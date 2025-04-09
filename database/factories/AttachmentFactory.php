<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserProfile>
 */
class AttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $extention = fake()->fileExtension();

        return [
            'attachable_id' => fake()->numberBetween(1, 10000),
            'attachable_type' => User::class,
            'hash_name' => fake()->uuid.'.'.$extention,
            'file_name' => fake()->lexify('????????').'.'.$extention,
            'file_size' => fake()->numberBetween(1, 10000),
            'mime_type' => fake()->mimeType(),
        ];
    }
}
