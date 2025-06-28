<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Models\MentorProgram;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('Mentor Program Edit Page', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->seed(CurrencySeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
    });

    it('renders mentor program edit page', function (): void {
        actingAs($this->user);

        $mentorProgram = MentorProgram::factory()->create();
        $response = get(route('mentor-program.edit', $mentorProgram->slug));

        $response->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('MentorProgram/CreateOrEdit')
            ->has('currencies', 4)
            ->whereContains('currencies', 'UAH')
            ->whereContains('currencies', 'USD')
            ->whereContains('currencies', 'EUR')
            ->whereContains('currencies', 'GBP')
            ->has('program')
            ->where('program.id', $mentorProgram->getKey())
            ->where('program.name', $mentorProgram->name)
            ->where('program.slug', $mentorProgram->slug)
            ->where('program.description', $mentorProgram->description)
            ->where('program.cost', $mentorProgram->cost)
            ->where('program.currency_id', $mentorProgram->currency_id)
        );
    });

    it('ensures mentor program creation page is accessible', function (): void {
        $mentorProgram = MentorProgram::factory()->create();
        $response = $this->actingAs($this->user)->get(route('mentor-program.edit', $mentorProgram->slug));

        $response->assertStatus(Response::HTTP_OK);
    });

    it('denies access to users without the mentor role', function (): void {
        $user = User::factory()->create();
        actingAs($user);

        $mentorProgram = MentorProgram::factory()->create();
        $response = get(route('mentor-program.edit', $mentorProgram->slug));

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    });
});
