<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Http\Resources\ChatUserResource;
use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;

describe('Chat User Resource', function (): void {
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

        $resource = ChatUserResource::make($user->profile)->resolve();

        expect($resource)->toMatchArray([
            'name'        => 'profile name',
            'last_name'   => 'profile last_name',
            'avatar'      => UserProfile::DEFAULT_AVATAR_URL,
        ]);
    });
});
