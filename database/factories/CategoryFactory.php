<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = (string) fake()->unique()->words(fake()->numberBetween(1, 3), true);

        return [
            'name'      => ucfirst($name),
            'slug'      => Str::slug($name),
            'parent_id' => null,
        ];
    }

    public function child(Category $parent): static
    {
        return $this->state(['parent_id' => $parent->getKey()]);
    }
}
