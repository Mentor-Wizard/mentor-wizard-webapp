<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MentorSessionNote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MentorSessionNote>
 */
class MentorSessionNoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'notes' => fake()->sentence(10),
        ];
    }
}
