<?php

declare(strict_types=1);

use App\Filament\Resources\User\Pages\CreateUser;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([RoleSeeder::class, CurrencySeeder::class]);
    $this->actingAs(User::factory()->create());
    Filament::setCurrentPanel('app');
});

describe('UserForm Schema', function (): void {
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
            ->set('data.username', 'testuser')
            ->set('data.email', 'test@example.com')
            ->set('data.password', 'password123')
            ->call('create')
            ->assertHasNoErrors();
    });
});
