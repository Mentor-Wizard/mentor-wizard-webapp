<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Throwable;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        try {
            DB::beginTransaction();

            foreach (RoleEnum::cases() as $role) {
                Role::query()->createOrFirst(['name' => $role->value]);
            }

            DB::commit();
        } catch (Throwable $throwable) {
            Log::error('[RoleSeeder] Roles are not added to DB', ['error' => $throwable->getMessage()]);
            DB::rollBack();
        }
    }
}
