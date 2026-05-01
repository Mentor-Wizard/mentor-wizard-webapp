<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CacheCategoryTree extends Command
{
    public const string CACHE_KEY = 'categories.tree';

    public const int CACHE_TTL = 3600;

    public const int MAX_DEPTH = 3;

    protected $signature = 'categories:cache-tree';

    protected $description = 'Build and store the category tree in Redis (TTL 1 hour)';

    /**
     * Returns the cached tree, building and caching it on a miss.
     *
     * @return array<int, array{id: int, name: string, children: array<mixed>}>
     */
    public static function getOrBuild(): array
    {
        /** @var array<int, array{id: int, name: string, children: array<mixed>}>|null $cached */
        $cached = Cache::get(self::CACHE_KEY);

        if ($cached !== null) {
            return $cached;
        }

        $tree = self::build();
        Cache::put(self::CACHE_KEY, $tree, self::CACHE_TTL);

        return $tree;
    }

    /**
     * Collects the target node's ID plus all descendant IDs from the cached tree array.
     *
     * @param  array<int, array{id: int, name: string, children: array<mixed>}>  $tree
     * @return array<int, int>
     */
    public static function collectDescendantIds(array $tree, int $targetId): array
    {
        foreach ($tree as $node) {
            if ($node['id'] === $targetId) {
                return self::flattenIds($node);
            }

            $found = self::collectDescendantIds($node['children'], $targetId);

            if ($found !== []) {
                return $found;
            }
        }

        return [];
    }

    /**
     * Rebuilds and caches the tree unconditionally, returning the fresh tree.
     *
     * @return array<int, array{id: int, name: string, children: array<mixed>}>
     */
    public static function refresh(): array
    {
        $tree = self::build();
        Cache::put(self::CACHE_KEY, $tree, self::CACHE_TTL);

        return $tree;
    }

    public function handle(): int
    {
        self::refresh();

        $this->info('Category tree cached successfully.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{id: int, name: string, children: array<mixed>}>
     */
    private static function build(): array
    {
        return Category::query()
            ->roots()
            ->with('children.children.children')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $node): array => self::serializeNode($node))
            ->all();
    }

    /**
     * @return array{id: int, name: string, children: array<mixed>}
     */
    private static function serializeNode(Category $node): array
    {
        return [
            'id'       => $node->getKey(),
            'name'     => $node->name,
            'children' => $node->children
                ->map(fn (Category $child): array => self::serializeNode($child))
                ->all(),
        ];
    }

    /**
     * @param  array{id: int, name: string, children: array<mixed>}  $node
     * @return array<int, int>
     */
    private static function flattenIds(array $node): array
    {
        $ids = [$node['id']];

        foreach ($node['children'] as $child) {
            array_push($ids, ...self::flattenIds($child));
        }

        return $ids;
    }
}
