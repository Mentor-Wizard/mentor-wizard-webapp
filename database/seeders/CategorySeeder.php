<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        /** @var array<string, array{slug: string, children?: array<string, mixed>}> $categories */
        $categories = config('starting_categories', []);

        $this->seedTree($categories, null);
    }

    /**
     * @param  array<string, array{slug: string, children?: array<string, mixed>}>  $tree
     */
    private function seedTree(array $tree, ?int $parentId): void
    {
        foreach ($tree as $name => $data) {
            $category = Category::query()->firstOrCreate(
                ['slug' => $data['slug']],
                ['name' => $name, 'parent_id' => $parentId],
            );

            $children = $data['children'] ?? [];

            if ($children !== []) {
                $this->seedTree($children, $category->getKey());
            }
        }
    }
}
