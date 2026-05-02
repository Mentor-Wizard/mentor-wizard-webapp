<?php

declare(strict_types=1);

use App\Filament\Resources\Category\CategoryResource;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;

beforeEach(function (): void {
    $this->seed([RoleSeeder::class]);

    Filament::setCurrentPanel('app');
});

describe('CategoryResource access control', function (): void {
    it('allows admin users to access the resource', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin);

        expect(CategoryResource::canAccess())->toBeTrue();
    });

    it('forbids users without the admin role from accessing the resource', function (string $role): void {
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user);

        expect(CategoryResource::canAccess())->toBeFalse();
    })->with([
        'mentor' => 'mentor',
        'menti'  => 'menti',
        'coach'  => 'coach',
        'user'   => 'user',
    ]);

    it('forbids users with no roles from accessing the resource', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user);

        expect(CategoryResource::canAccess())->toBeFalse();
    });

    it('forbids guests from accessing the resource', function (): void {
        expect(CategoryResource::canAccess())->toBeFalse();
    });
});
