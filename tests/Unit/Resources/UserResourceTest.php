<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Http\Resources\UserResource;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;

describe('Mentor Page', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    it('correctly transforms user resource', function (): void {
        $user = User::factory()->create([
            'username' => 'Test User',
            'email'    => 'test@example.com',
        ]);

        $user->refresh();
        $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $resource = UserResource::make($user)->resolve();

        expect($resource)->toMatchArray([
            'id'          => $user->id,
            'username'    => 'Test User',
            'email'       => 'test@example.com',
            'created_at'  => $user->created_at,
            'rating'      => 0,
        ]);
    });
});
