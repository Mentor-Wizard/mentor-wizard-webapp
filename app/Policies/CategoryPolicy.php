<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Category $category): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->hasRole('admin');
    }
}
