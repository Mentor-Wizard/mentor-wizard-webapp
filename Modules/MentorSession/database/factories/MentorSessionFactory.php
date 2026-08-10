<?php

declare(strict_types=1);

namespace Modules\MentorSession\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\MentorSession\Models\MentorSession;
use Override;

/**
 * @extends Factory<MentorSession>
 */
class MentorSessionFactory extends Factory
{
    #[Override]
    protected $model = MentorSession::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mentor_id'       => User::factory(),
            'menti_id'        => User::factory(),
            'date'            => fake()->dateTimeBetween('now', '+3 month'),
            'is_success'      => fake()->boolean(),
            'is_paid'         => fake()->boolean(),
            'is_cancelled'    => fake()->boolean(),
            'is_date_changed' => fake()->boolean(),
            'cost'            => fake()->randomFloat(2, 10, 1000),
        ];
    }
}
