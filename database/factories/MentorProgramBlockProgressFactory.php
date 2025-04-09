<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MentorProgramBlock;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MentorProgramBlockProgress>
 */
class MentorProgramBlockProgressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mentor_program_block_id' => MentorProgramBlock::factory(),
            'menti_id' => User::factory(),
            'is_completed' => fake()->boolean(),
        ];
    }
}
