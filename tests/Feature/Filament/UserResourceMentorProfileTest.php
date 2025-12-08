<?php

declare(strict_types=1);

use App\Filament\Resources\User\Pages\CreateUser;
use App\Filament\Resources\User\Pages\EditUser;
use App\Models\Currency;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([RoleSeeder::class, CurrencySeeder::class]);

    $this->actingAs(User::factory()->create());

    Filament::setCurrentPanel('app');
});

describe('UserResource Mentor Profile Fields', function (): void {
    it('can create user with mentor profile when mentor role is selected', function (): void {
        $currency = Currency::query()->first();
        $mentorRole = Role::query()->where('name', 'mentor')->first();

        Livewire::test(CreateUser::class)
            ->fillForm([
                'username'                            => 'mentoruser',
                'email'                               => 'mentor@example.com',
                'password'                            => 'password123',
                'roles'                               => [$mentorRole->getKey()],
                'profile.name'                        => 'John',
                'profile.last_name'                   => 'Doe',
                'mentorProfile.title'                 => 'Senior Software Engineer',
                'mentorProfile.description'           => 'Experienced developer with 10+ years',
                'mentorProfile.rate'                  => '100.00',
                'mentorProfile.currency_id'           => $currency->id,
                'mentorProfile.experience_started_at' => '2013-01-01',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::query()->where('email', 'mentor@example.com')->first();
        expect($user)->not->toBeNull()
            ->and($user->hasRole('mentor'))->toBeTrue()
            ->and($user->mentorProfile)->not->toBeNull()
            ->and($user->mentorProfile->title)->toBe('Senior Software Engineer')
            ->and($user->mentorProfile->description)->toBe('Experienced developer with 10+ years')
            ->and($user->mentorProfile->rate)->toBe('100.00');
    });

    it('shows mentor profile fields in edit form when user has mentor role', function (): void {
        $currency = Currency::query()->first();
        $user = User::factory()->create();
        $user->assignRole('mentor');
        $user->mentorProfile()->create([
            'title'                 => 'Test Title',
            'description'           => 'Test Description',
            'rate'                  => '50.00',
            'currency_id'           => $currency->id,
            'experience_started_at' => '2013-01-01',
        ]);

        $component = Livewire::test(EditUser::class, ['record' => $user->getKey()]);

        expect($component)->not->toBeNull();
    });

    it('persists experience_started_at when editing mentor profile', function (): void {
        $currency = Currency::query()->first();
        $user = User::factory()->create();
        $user->assignRole('mentor');
        $user->mentorProfile()->create([
            'title'                 => 'Initial Title',
            'description'           => 'Initial Description',
            'rate'                  => '50.00',
            'currency_id'           => $currency->id,
            'experience_started_at' => '2013-01-01',
        ]);

        Livewire::test(EditUser::class, ['record' => $user->getKey()])
            ->fillForm([
                'mentorProfile.title'                 => 'Updated Title',
                'mentorProfile.description'           => 'Updated Description',
                'mentorProfile.rate'                  => '60.00',
                'mentorProfile.currency_id'           => $currency->id,
                'mentorProfile.experience_started_at' => '2013-01-01',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        expect($user->mentorProfile->experience_started_at->format('Y-m-d'))
            ->toBe('2013-01-01');
    });

    it('validates required mentor profile fields when mentor role is selected', function (): void {
        $mentorRole = Role::query()->where('name', 'mentor')->first();

        Livewire::test(CreateUser::class)
            ->fillForm([
                'username'          => 'mentoruser',
                'email'             => 'mentor@example.com',
                'password'          => 'password123',
                'roles'             => [$mentorRole->getKey()],
                'profile.name'      => 'John',
                'profile.last_name' => 'Doe',
                // Intentionally omitting required mentor profile fields
            ])
            ->call('create')
            ->assertHasFormErrors([
                'mentorProfile.rate',
                'mentorProfile.currency_id',
                'mentorProfile.experience_started_at',
            ]);
    });

    it('validates individual required mentor profile fields', function (string $field): void {
        $currency = Currency::query()->first();
        $mentorRole = Role::query()->where('name', 'mentor')->first();

        $formData = [
            'username'                            => 'mentoruser',
            'email'                               => 'mentor@example.com',
            'password'                            => 'password123',
            'roles'                               => [$mentorRole->getKey()],
            'profile.name'                        => 'John',
            'profile.last_name'                   => 'Doe',
            'mentorProfile.title'                 => 'Senior Software Engineer',
            'mentorProfile.description'           => 'Experienced developer',
            'mentorProfile.rate'                  => '100.00',
            'mentorProfile.currency_id'           => $currency->id,
            'mentorProfile.experience_started_at' => '2013-01-01',
        ];

        // Remove the field being tested
        unset($formData[$field]);

        Livewire::test(CreateUser::class)
            ->fillForm($formData)
            ->call('create')
            ->assertHasFormErrors([$field]);
    })->with([
        'mentorProfile.rate',
        'mentorProfile.currency_id',
        'mentorProfile.experience_started_at',
    ]);
});
