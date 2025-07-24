<?php

namespace Database\Factories;

use App\Enums\TagEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MentorTag>
 */
class MentorTagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement([TagEnum::STACK, TagEnum::LANGUAGE]),
            'tag' => $this->faker->word(),
        ];
    }
}
