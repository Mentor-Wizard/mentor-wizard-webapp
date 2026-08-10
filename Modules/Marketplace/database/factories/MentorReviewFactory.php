<?php

declare(strict_types=1);

namespace Modules\Marketplace\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Marketplace\Models\MentorReview;
use Override;

/**
 * @extends Factory<MentorReview>
 */
class MentorReviewFactory extends Factory
{
    #[Override]
    protected $model = MentorReview::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mentor_id' => User::factory(),
            'menti_id'  => User::factory(),
            'comment'   => fake()->sentence(10),
            'rating'    => fake()->numberBetween(1, 5),
        ];
    }
}
