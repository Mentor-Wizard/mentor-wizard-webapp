<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\MentorProgram;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
            'name' => Str::random(100),
            'slug' => fake()->slug(),
            'description' => fake()->sentence(20),
        ];
    }
}
