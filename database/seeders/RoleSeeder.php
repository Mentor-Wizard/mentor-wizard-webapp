<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Enums\RoleGuardEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Throwable;

class RoleSeeder extends Seeder
{
    private const array ROLES = [
        ['name' => RoleEnum::USER->value, 'guard_name' => RoleGuardEnum::USER->value],
        ['name' => RoleEnum::ADMIN->value, 'guard_name' => RoleGuardEnum::ADMIN->value],
        ['name' => RoleEnum::SUPER_ADMIN->value, 'guard_name' => RoleGuardEnum::SUPER_ADMIN->value],
        ['name' => RoleEnum::MENTOR->value, 'guard_name' => RoleGuardEnum::MENTOR->value],
        ['name' => RoleEnum::MENTOR->value, 'guard_name' => 'web'],
        ['name' => RoleEnum::MENTI->value, 'guard_name' => RoleGuardEnum::MENTI->value],
        ['name' => RoleEnum::COACH->value, 'guard_name' => RoleGuardEnum::COACH->value],
    ];

    public function run(): void
    {
        try {
            DB::beginTransaction();

            collect(self::ROLES)->each(function ($role): void {
                Role::query()->createOrFirst($role);
            });

            DB::commit();
        } catch (Throwable $throwable) {
            Log::error('[RoleSeeder] Roles are not added to DB', ['error' => $throwable->getMessage()]);
            DB::rollBack();
        }
    }
}
