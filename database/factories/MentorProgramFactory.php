<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Currency;
use App\Models\MentorProgram;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MentorProgram>
 */
class MentorProgramFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mentor_id' => User::factory(),
            'name' => Str::random(100),
            'slug' => fake()->slug(),
            'description' => fake()->sentence(20),
            'cost' => fake()->randomFloat(2, 10, 1000),
            'currency_id' => Currency::factory(),
        ];
    }
}
