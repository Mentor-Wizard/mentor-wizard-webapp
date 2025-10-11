<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MentorProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MentorProgram>
 */
class MentorProgramBlockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mentor_program_id' => MentorProgram::factory(),
            'name'              => fake()->sentence(3),
            'slug'              => fake()->slug(),
            'description'       => fake()->sentence(20),
        ];
    }
}
