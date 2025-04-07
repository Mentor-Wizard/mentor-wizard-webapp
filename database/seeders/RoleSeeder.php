<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Enums\RoleGuardEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    private const array ROLES = [
        ['name' => RoleEnum::USER, 'guard_name' => RoleGuardEnum::USER],
        ['name' => RoleEnum::ADMIN, 'guard_name' => RoleGuardEnum::ADMIN],
        ['name' => RoleEnum::SUPER_ADMIN, 'guard_name' => RoleGuardEnum::SUPER_ADMIN],
        ['name' => RoleEnum::MENTOR, 'guard_name' => RoleGuardEnum::MENTOR],
        ['name' => RoleEnum::MENTI, 'guard_name' => RoleGuardEnum::MENTI],
        ['name' => RoleEnum::COACH, 'guard_name' => RoleGuardEnum::COACH],
    ];

    public function run(): void
    {
        try {
            DB::beginTransaction();

            collect(self::ROLES)->each(function ($role) {
                Role::query()->createOrFirst($role);
            });

            DB::commit();
        } catch (\Throwable $e) {
            Log::error('[RoleSeeder] Roles are not added to DB', ['error' => $e->getMessage()]);
            DB::rollBack();
        }
    }
}
