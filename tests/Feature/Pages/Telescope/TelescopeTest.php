<?php

use App\Enums\RoleEnum;
use App\Enums\RoleGuardEnum;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Laravel\Telescope\Storage\EntryModel;
use Spatie\Permission\Models\Role;

describe('Telescope Page', function () {
    beforeEach(function () {
        $this->seed(RoleSeeder::class);
    });

    it('telescope is accessible for a user in non-local environment', function () {
        $user = User::factory()->create([
            'username' => 'Test USER',
            'email' => 'user@example.com',
        ]);

        $this->actingAs($user)
            ->get('/telescope')
            ->assertOk();
    });

    it('telescope is accessible for an admin in non-local environment', function () {
        $role = Role::findByName(RoleEnum::ADMIN->value, RoleGuardEnum::ADMIN->value);
        $admin = User::factory()->create([
            'username' => 'Test ADMIN',
            'email' => 'admin@example.com',
        ]);

        $admin->syncRoles($role);

        $this->actingAs($admin)
            ->get('/telescope')
            ->assertOk();
    });

    it('telescope avoids loging healthchecks in database', function () {
        $user = User::factory()->create([
            'username' => 'Test USER1',
            'email' => 'admi@example1.com',
        ]);

        $this->actingAs($user)
            ->get('/up');

        expect(EntryModel::where('type', 'request')->whereJsonContains('content', ['uri' => '/up'])
            ->exists())->toBeFalse()
            ->and(EntryModel::where('type', 'view')->whereJsonContains('content', ['name' => 'health-up.blade.php'])
                ->exists())->toBeFalse();
    });

    it('telescope successfully logs info about request into database', function () {
        $user = User::factory()->create([
            'username' => 'Test USER2',
            'email' => 'admi@example.com2',
        ]);

        $this->actingAs($user)
            ->get('/');

        expect(EntryModel::where('type', 'request')->whereJsonContains('content', ['uri' => '/'])
            ->exists())->toBeTrue()
            ->and(EntryModel::where('type', 'view')->whereJsonContains('content', ['name' => 'app'])
                ->exists())->toBeTrue();
    });

})->skip('temporarily to run it on CI');
