<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Http\Resources\UserProfileResource;
use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;

covers(UserProfileResource::class);

describe('User Profile Resource', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    it('correctly transforms user resource', function (): void {
        $user = User::factory()->create([
            'username' => 'Test User',
            'email'    => 'test@example.com',
        ]);
        $user->profile->update([
            'name'        => 'profile name',
            'last_name'   => 'profile last_name',
            'linkedin'    => 'profile linkedin',
            'telegram'    => 'profile telegram',
            'whatsapp'    => 'profile whatsapp',
            'phone'       => 'profile phone',
        ]);
        $user->refresh();
        $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $resource = UserProfileResource::make($user->profile)->resolve();

        expect($resource)->toMatchArray([
            'name'        => 'profile name',
            'last_name'   => 'profile last_name',
            'linkedin'    => 'profile linkedin',
            'telegram'    => 'profile telegram',
            'whatsapp'    => 'profile whatsapp',
            'phone'       => 'profile phone',
            'avatar'      => UserProfile::DEFAULT_AVATAR_URL,
        ]);
    });
});
