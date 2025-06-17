<?php

use App\Enums\RoleEnum;
use App\Enums\RoleGuardEnum;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;

describe('Mentor Page', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    it('correctly transforms user resource', function () {
        $user = User::factory()->create([
            'username' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $user->profile->update([
            'name' => 'profile name',
            'last_name' => 'profile last_name',
            'linkedin' => 'profile linkedin',
            'telegram' => 'profile telegram',
            'whatsapp' => 'profile whatsapp',
            'phone' => 'profile phone',
            'description' => 'profile description',
        ]);
        $user->refresh();
        $user->assignRole(Role::findByName(RoleEnum::MENTOR->value, RoleGuardEnum::MENTOR->value));

        $resource = UserResource::make($user)->resolve();

        expect($resource)->toMatchArray([
            'id' => $user->id,
            'username' => 'Test User',
            'email' => 'test@example.com',
            'created_at' => $user->created_at,
            'name' => 'profile name',
            'last_name' => 'profile last_name',
            'linkedin' => 'profile linkedin',
            'telegram' => 'profile telegram',
            'whatsapp' => 'profile whatsapp',
            'phone' => 'profile phone',
            'description' => 'profile description',
            'avatar' => '',
            'rating' => 0,
        ]);
    });
});
