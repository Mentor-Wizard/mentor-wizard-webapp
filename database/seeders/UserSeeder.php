<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    protected Collection $roles;

    public function __construct()
    {
        $this->roles = Role::query()->get();
    }

    public function run(): void
    {
        User::factory()
            ->count(50)
            ->create()
            ->each(function ($user): void {
                $user->assignRole($this->roles->random());
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
