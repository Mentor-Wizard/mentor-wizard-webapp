<?php

declare(strict_types=1);

use App\Filament\Resources\User\Pages\CreateUser;
use App\Filament\Resources\User\Schemas\UserForm;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Create roles directly instead of running an expensive seeder
    Role::query()->firstOrCreate(['name' => 'mentor']);
    Role::query()->firstOrCreate(['name' => 'user']);
    $this->actingAs(User::factory()->create());
    Filament::setCurrentPanel('app');
});

describe('UserForm Schema', function (): void {
    it('generates the correct form schema', function (): void {
        $userForm = new UserForm;
        $schema = $userForm->schema([]);

        expect($schema)->toBeInstanceOf(UserForm::class);
    });

    it('validates username field requirements through form interaction', function (): void {
        Livewire::test(CreateUser::class)
            ->fillForm([
                'email'    => 'test@example.com',
                'password' => 'password123',
            ])
            ->call('create')
            ->assertHasFormErrors(['username']);
    });

    it('validates email field requirements through form interaction', function (): void {
        Livewire::test(CreateUser::class)
            ->fillForm([
                'username' => 'testuser',
                'password' => 'password123',
            ])
            ->call('create')
            ->assertHasFormErrors(['email']);
    });

    it('validates password field requirements through form interaction', function (): void {
        Livewire::test(CreateUser::class)
            ->fillForm([
                'username' => 'testuser',
                'email'    => 'test@example.com',
            ])
            ->call('create')
            ->assertHasFormErrors(['password']);
    });

    it('validates password minimum length through form interaction', function (): void {
        Livewire::test(CreateUser::class)
            ->fillForm([
                'username' => 'testuser',
                'email'    => 'test@example.com',
                'password' => '123', // Too short
            ])
            ->call('create')
            ->assertHasFormErrors(['password']);
    });

    it('accepts valid form data', function (): void {
        Livewire::test(CreateUser::class)
            ->fillForm([
                'username' => 'testuser',
                'email'    => 'test@example.com',
                'password' => 'password123',
            ])
            ->call('create')
            ->assertHasNoFormErrors(['username', 'email', 'password']);
    });
});
