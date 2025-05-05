<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Enums\RoleGuardEnum;
use App\Models\Currency;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

describe('Mentor Program Store Page', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->seed(CurrencySeeder::class);
        $this->currencies = Currency::query()->pluck('name', 'id')->toArray();

        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value, RoleGuardEnum::MENTOR->value));
    });

    it('creates a mentor program successfully', function (): void {
        actingAs($this->user);

        $programData = [
            'name'        => 'New Mentor Program',
            'description' => 'This is a description for the new mentor program.',
            'cost'        => 200.0,
            'currency_id' => array_key_first($this->currencies),
        ];

        $response = post(route('mentor-program.store'), $programData);

        $response->assertRedirect(route('mentor-program.create'));

        $this->assertDatabaseHas('mentor_programs', [
            'mentor_id'   => $this->user->id,
            'name'        => 'New Mentor Program',
            'description' => 'This is a description for the new mentor program.',
            'cost'        => 200.0,
            'currency_id' => array_key_first($this->currencies),
        ]);
    });

    it('validates input when storing mentor program', function (): void {
        actingAs($this->user);

        $invalidData = [
            'name'        => '',
            'description' => '',
            'cost'        => -50,
            'currency_id' => null,
        ];

        $response = post(route('mentor-program.store'), $invalidData);

        $response->assertSessionHasErrors(['name', 'description', 'cost', 'currency_id']);
    });

    it('throws 403 when a non-mentor user tries to create a mentor program', function (): void {
        $nonMentorUser = User::factory()->create();

        actingAs($nonMentorUser);

        $programData = [
            'name'        => 'Unauthorized Program',
            'description' => 'This should not be created.',
            'cost'        => 100.0,
            'currency_id' => array_key_first($this->currencies),
        ];

        $response = post(route('mentor-program.store'), $programData);

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertDatabaseMissing('mentor_programs', [
            'name' => 'Unauthorized Program',
        ]);
    });
});
