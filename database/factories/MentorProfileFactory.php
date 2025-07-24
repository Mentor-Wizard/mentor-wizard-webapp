<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Currency;
use App\Models\MentorProfile;
use App\Models\User;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MentorProfile>
 */
class MentorProfileFactory extends Factory
{
    protected $model = MentorProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'               => User::factory(),
            'title'                 => fake()->jobTitle(),
            'description'           => fake()->sentence(),
            'rate'                  => fake()->randomFloat(2, 10, 100),
            'currency_id'           => Currency::query()->inRandomOrder()->value('id') ?? Currency::factory(),
            'experience_started_at' => fake()->dateTimeBetween('-15 year', '-1 year'),
        ];
    }
}
