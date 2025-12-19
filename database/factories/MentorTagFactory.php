<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TagEnum;
use App\Models\MentorTag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MentorTag>
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
            'tag'  => fake()->word(),
        ];
    }
}
