<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Enums\RoleGuardEnum;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()
            ->count(10)
            ->create()
            ->each(function ($user) {
                $user->assignRole(RoleEnum::MENTOR->value);
                $user->profile()->update(UserProfile::factory()->make()->toArray());
                $name = urlencode($user->profile->name . ' ' . $user->profile->last_name);
                $avatarUrl = "https://ui-avatars.com/api/?name={$name}&background=random&size=256&format=png";
                try {
                    $user->profile->addMediaFromUrl($avatarUrl)
                        ->usingFileName('avatar.png')
                        ->toMediaCollection('avatar');
                } catch (\Exception $e) {
                }
            });
    }
}
