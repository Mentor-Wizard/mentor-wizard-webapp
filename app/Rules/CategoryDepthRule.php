<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Category;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CategoryDepthRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        $parentDepth = $this->resolveDepth((int) $value);

        if ($parentDepth >= Category::MAX_DEPTH) {
            $fail('Categories cannot be nested deeper than '.Category::MAX_DEPTH.' levels.');
        }
    }

    private function resolveDepth(int $parentId): int
    {
        $depth = 0;
        $currentId = $parentId;

        while ($currentId !== null && $depth <= Category::MAX_DEPTH) {
            $depth++;
            $category = Category::query()->select(['id', 'parent_id'])->find($currentId);
            $currentId = $category?->parent_id;
        }

        return $depth;
    }
}
