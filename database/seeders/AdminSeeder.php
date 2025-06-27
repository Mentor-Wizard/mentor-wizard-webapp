<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Enums\RoleGuardEnum;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role = Role::findByName(RoleEnum::ADMIN->value, RoleGuardEnum::ADMIN->value);
        User::factory()
            ->count(10)
            ->create()
            ->each(function ($user) use ($role): void {
                $user->assignRole($role);
                $user->profile()->update([
                    'name'          => explode(' ', $user->username)[0],
                    'last_name'     => explode(' ', $user->username)[1],
                    'title'         => fake()->jobTitle(),
                    'linkedin'      => fake()->url,
                    'telegram'      => fake()->userName,
                    'whatsapp'      => fake()->phoneNumber,
                    'phone'         => fake()->phoneNumber,
                    'description'   => fake()->text(),
                ]
                );
                $name = urlencode($user->profile->name.' '.$user->profile->last_name);
                $avatarUrl = sprintf(UserProfile::TEST_AVATAR_URL, $name);
                $user->profile->addMediaFromUrl($avatarUrl)
                    ->usingFileName('avatar.png')
                    ->toMediaCollection('avatar');
            });
    }
}
