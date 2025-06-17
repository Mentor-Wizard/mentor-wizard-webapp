<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\RoleEnum;
use App\Enums\RoleGuardEnum;
use App\Models\User;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserObserver
{
    public function created(User $user): void
    {
        $user->assignRole(Role::findByName(RoleEnum::USER->value, RoleGuardEnum::USER->value));
        $user->profile()->create();
        $user->slug = $this->generateUniqueSlug($user->email);
        $user->save();
    }

    protected function generateUniqueSlug(string $base): string
    {
        $slug = Str::slug($base);
        $originalSlug = $slug;
        $counter = 1;

        while (\App\Models\User::query()->where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$counter++;
        }

        return $slug;
    }
}
