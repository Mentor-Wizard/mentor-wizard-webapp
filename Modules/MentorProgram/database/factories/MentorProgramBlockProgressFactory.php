<?php

declare(strict_types=1);

namespace Modules\MentorProgram\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\MentorProgram\Models\MentorProgramBlock;
use Modules\MentorProgram\Models\MentorProgramBlockProgress;

/**
 * @extends Factory<MentorProgramBlockProgress>
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
            'menti_id'                => User::factory(),
            'is_completed'            => fake()->boolean(),
        ];
    }
}
