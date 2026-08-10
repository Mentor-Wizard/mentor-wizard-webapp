<?php

declare(strict_types=1);

namespace Modules\MentorSession\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\MentorSession\Models\MentorSessionNote;
use Override;

/**
 * @extends Factory<MentorSessionNote>
 */
class MentorSessionNoteFactory extends Factory
{
    #[Override]
    protected $model = MentorSessionNote::class;

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
