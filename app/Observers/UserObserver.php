<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\RoleEnum;
use App\Enums\RoleGuardEnum;
use App\Models\User;
use Spatie\Permission\Models\Role;

class UserObserver
{
    public function created(User $user): void
    {
        $user->assignRole(Role::findByName(RoleEnum::USER->value, RoleGuardEnum::USER->value));
    }
}
