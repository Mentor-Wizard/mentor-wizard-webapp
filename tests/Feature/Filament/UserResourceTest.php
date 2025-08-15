<?php

declare(strict_types=1);

use App\Filament\Resources\User\Pages\CreateUser;
use App\Filament\Resources\User\Pages\EditUser;
use App\Filament\Resources\User\Pages\ListUsers;
use App\Models\Currency;
use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

describe('Filament UserResource', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        // Create an admin user to access Filament
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        actingAs($admin);

        // Set current panel for Filament
        Filament::setCurrentPanel('admin');

        // Create a test currency
        $this->currency = Currency::factory()->create([
            'name'   => 'USD',
            'slug'   => 'usd',
            'symbol' => '$',
        ]);
    });

    it('can render user list page', function (): void {
        $users = User::factory()->count(5)->create();

        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords($users);
    });

    it('can create user with profile information', function (): void {
        $userData = [
            'username' => 'testuser',
            'email'    => 'test@example.com',
            'password' => 'password123',
            'roles'    => [],
        ];

        $profileData = [
            'profile.name'          => 'John',
            'profile.last_name'     => 'Doe',
            'profile.phone'         => '+1234567890123',
            'profile.currency_id'   => $this->currency->id,
            'profile.cost_per_hour' => 50.00,
            'profile.linkedin'      => 'https://www.linkedin.com/in/johndoe',
            'profile.telegram'      => 'https://t.me/johndoe',
            'profile.whatsapp'      => 'https://wa.me/1234567890',
        ];

        Livewire::test(CreateUser::class)
            ->fillForm(array_merge($userData, $profileData))
            ->call('create')
            ->assertNotified();

        $this->assertDatabaseHas('users', [
            'username' => 'testuser',
            'email'    => 'test@example.com',
        ]);

        $user = User::query()->where('email', 'test@example.com')->first();
        expect($user)->not->toBeNull();

        $this->assertDatabaseHas('user_profiles', [
            'user_id'       => $user->id,
            'name'          => 'John',
            'last_name'     => 'Doe',
            'phone'         => '+1234567890123',
            'currency_id'   => $this->currency->id,
            'cost_per_hour' => 50.00,
            'linkedin'      => 'https://www.linkedin.com/in/johndoe',
            'telegram'      => 'https://t.me/johndoe',
            'whatsapp'      => 'https://wa.me/1234567890',
        ]);
    });

    it('can edit user with profile information', function (): void {
        $user = User::factory()->create([
            'username' => 'originaluser',
            'email'    => 'original@example.com',
        ]);

        $profile = UserProfile::factory()->create([
            'user_id'     => $user->id,
            'name'        => 'Original',
            'last_name'   => 'User',
            'currency_id' => $this->currency->id,
        ]);

        $updatedData = [
            'username'              => 'updateduser',
            'email'                 => 'updated@example.com',
            'profile.name'          => 'Updated',
            'profile.last_name'     => 'Person',
            'profile.phone'         => '+9876543210123',
            'profile.cost_per_hour' => 75.50,
            'profile.linkedin'      => 'https://www.linkedin.com/in/updatedperson',
        ];

        Livewire::test(EditUser::class, [
            'record' => $user->getRouteKey(),
        ])
            ->fillForm($updatedData)
            ->call('save')
            ->assertNotified();

        $this->assertDatabaseHas('users', [
            'id'       => $user->id,
            'username' => 'updateduser',
            'email'    => 'updated@example.com',
        ]);

        $this->assertDatabaseHas('user_profiles', [
            'user_id'       => $user->id,
            'name'          => 'Updated',
            'last_name'     => 'Person',
            'phone'         => '+9876543210123',
            'cost_per_hour' => 75.50,
            'linkedin'      => 'https://www.linkedin.com/in/updatedperson',
        ]);
    });

    it('validates profile fields according to rules', function (): void {
        Livewire::test(CreateUser::class)
            ->fillForm([
                'username'          => 'testuser',
                'email'             => 'test@example.com',
                'password'          => 'password123',
                'profile.name'      => 'A', // Too short
                'profile.last_name' => 'B', // Too short
                'profile.phone'     => 'invalid-phone', // Invalid format
                'profile.linkedin'  => 'not-a-linkedin-url', // Invalid URL
                'profile.telegram'  => 'not-a-telegram-url', // Invalid URL
                'profile.whatsapp'  => 'not-a-whatsapp-url', // Invalid URL
            ])
            ->call('create')
            ->assertHasFormErrors([
                'profile.name'      => 'min',
                'profile.last_name' => 'min',
                'profile.phone'     => 'regex',
                'profile.linkedin'  => 'regex',
                'profile.telegram'  => 'regex',
                'profile.whatsapp'  => 'regex',
            ]);
    });

    it('allows optional profile fields to be empty', function (): void {
        Livewire::test(CreateUser::class)
            ->fillForm([
                'username'              => 'testuser',
                'email'                 => 'test@example.com',
                'password'              => 'password123',
                'profile.name'          => null,
                'profile.last_name'     => null,
                'profile.phone'         => null,
                'profile.linkedin'      => null,
                'profile.telegram'      => null,
                'profile.whatsapp'      => null,
                'profile.cost_per_hour' => null,
                'profile.currency_id'   => null,
            ])
            ->call('create')
            ->assertNotified();

        $this->assertDatabaseHas('users', [
            'username' => 'testuser',
            'email'    => 'test@example.com',
        ]);
    });
});
