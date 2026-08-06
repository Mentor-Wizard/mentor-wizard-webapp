<?php

declare(strict_types=1);

namespace Modules\Auth\Observers;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserObserver
{
    public function created(User $user): void
    {
        $user->assignRole(Role::findByName(RoleEnum::USER->value));
        $user->profile()->create(['timezone' => request()
            ->get('timezone') ?? config('app.timezone', 'UTC')]);
        $user->slug = $this->generateUniqueSlug($user->username);
        $user->save();
    }

    protected function generateUniqueSlug(string $base): string
    {
        $slug = Str::slug($base);
        $originalSlug = $slug;
        $counter = 1;

        while (User::query()->where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$counter++;
        }

        return $slug;
    }
}
