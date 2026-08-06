<?php

declare(strict_types=1);

namespace Modules\Marketplace\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Marketplace\Enums\TagEnum;
use Modules\Marketplace\Models\MentorTag;
use Override;

/**
 * @extends Factory<MentorTag>
 */
class MentorTagFactory extends Factory
{
    #[Override]
    protected $model = MentorTag::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement([TagEnum::STACK, TagEnum::LANGUAGE]),
            'tag'  => fake()->word(),
        ];
    }
}
