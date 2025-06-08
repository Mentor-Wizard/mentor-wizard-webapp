<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;

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
            ->each(function ($user): void {
                $user->assignRole(RoleEnum::MENTOR->value);
                $user->profile()->update(UserProfile::factory()->make()->toArray());
                $name = urlencode($user->profile->name.' '.$user->profile->last_name);
                $avatarUrl = sprintf('https://ui-avatars.com/api/?name=%s&background=random&size=256&format=png', $name);
                $user->profile->addMediaFromUrl($avatarUrl)
                    ->usingFileName('avatar.png')
                    ->toMediaCollection('avatar');
            });
    }
}
