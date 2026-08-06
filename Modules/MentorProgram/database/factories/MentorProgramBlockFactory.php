<?php

declare(strict_types=1);

namespace Modules\MentorProgram\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\MentorProgram\Models\MentorProgram;

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
