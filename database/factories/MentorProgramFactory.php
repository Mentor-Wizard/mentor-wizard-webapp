<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RoleEnum;
use App\Models\Currency;
use App\Models\MentorProgram;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'mentor_id'   => User::query()->role(RoleEnum::MENTOR->value)
                ->inRandomOrder()
                ->value('id'),
            'name'                  => fake()->sentence(3),
            'slug'                  => fake()->slug(),
            'is_main'               => false,
            'description'           => fake()->sentence(20),
            'cost'                  => fake()->randomFloat(2, 10, 1000),
            'session_duration'      => 60,
            'currency_id'           => Currency::query()->inRandomOrder()->value('id') ?? Currency::factory(),
            'need_confirmation'     => false,
        ];
    }

    public function main(): static
    {
        return $this->state(['is_main' => true]);
    }
}
