<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Enums\RoleGuardEnum;
use App\Models\Currency;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\RoleSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function (): void {
    DB::statement('ALTER SEQUENCE currencies_id_seq RESTART WITH 1');
    $this->seed(CurrencySeeder::class);
    $this->seed(RoleSeeder::class);

    $this->user = User::factory()->create();
    $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value, RoleGuardEnum::MENTOR->value));
});

it('renders mentor program creation page with currencies', function (): void {
    $response = $this->actingAs($this->user)->get(route('mentor-program.create'));

    $response->assertInertia(fn (Assert $page): Illuminate\Testing\Fluent\AssertableJson => $page
        ->component('MentorProgram/CreateOrEdit')
        ->has('currencies', 4)
        ->where('currencies.1', 'UAH')
        ->where('currencies.2', 'USD')
        ->where('currencies.3', 'EUR')
        ->where('currencies.4', 'GBP')
    );
});

it('throws exception when currencies table is empty', function (): void {
    $this->actingAs($this->user);
    Currency::query()->delete();

    $this->withoutExceptionHandling();

    expect(fn () => get(route('mentor-program.create')))
        ->toThrow(Exception::class, 'Currencies table is empty');
});

it('response contains required page structure', function (): void {
    $response = $this->actingAs($this->user)->get(route('mentor-program.create'));

    $response->assertInertia(fn (Assert $page): Illuminate\Testing\Fluent\AssertableJson => $page
        ->component('MentorProgram/CreateOrEdit')
        ->has('currencies')
    );
});

it('ensures mentor program creation page is accessible', function (): void {
    $response = $this->actingAs($this->user)->get(route('mentor-program.create'));

    $response->assertStatus(Response::HTTP_OK);
});

it('denies access to users without the mentor role', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    $response = get(route('mentor-program.create'));

    $response->assertStatus(Response::HTTP_FORBIDDEN);
});
