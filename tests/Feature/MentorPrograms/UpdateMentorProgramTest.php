<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Enums\RoleGuardEnum;
use App\Models\Currency;
use App\Models\MentorProgram;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

describe('Mentor Program Update Page', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->seed(CurrencySeeder::class);
        $this->currencies = Currency::query()->pluck('name', 'id')->toArray();

        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value, RoleGuardEnum::MENTOR->value));

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id'   => $this->user->getKey(),
            'name'        => 'Original Program Name',
            'slug'        => 'original-program',
            'description' => 'Original Description',
            'cost'        => 100.0,
            'currency_id' => array_key_first($this->currencies),
        ]);
    });

    it('updates mentor program successfully without changing the slug', function (): void {
        actingAs($this->user);

        $updatedData = [
            'name'        => 'Updated Program Name',
            'description' => 'Updated Description',
            'cost'        => 150.0,
            'currency_id' => array_keys($this->currencies)[1],
            'slug'        => $this->mentorProgram->slug,
        ];

        $response = patch(route('mentor-program.update', $this->mentorProgram->slug), $updatedData);

        $response->assertRedirect(route('mentor-program.edit', $this->mentorProgram->slug));

        $this->assertDatabaseHas('mentor_programs', [
            'id'          => $this->mentorProgram->getKey(),
            'name'        => 'Updated Program Name',
            'description' => 'Updated Description',
            'cost'        => 150.0,
            'currency_id' => array_keys($this->currencies)[1],
            'slug'        => $this->mentorProgram->slug,
        ]);
    });

    it('throws 404 when trying to update a non-existent mentor program', function (): void {
        actingAs($this->user);

        $nonExistentSlug = 'non-existent-slug';

        $response = patch(route('mentor-program.update', $nonExistentSlug), [
            'name'        => 'Updated Program Name',
            'description' => 'Updated Description',
            'cost'        => 150.0,
            'currency_id' => array_keys($this->currencies)[1],
        ]);

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    });

    it("throws 403 when trying to update another mentor's program", function (): void {
        $anotherUser = User::factory()->create();
        $anotherUser->assignRole(Role::findByName(RoleEnum::MENTOR->value, RoleGuardEnum::MENTOR->value));

        $anotherMentorProgram = MentorProgram::factory()->create([
            'mentor_id'   => $anotherUser->getKey(),
            'name'        => 'Another Program',
            'slug'        => 'another-program',
            'description' => 'Another Description',
            'cost'        => 75.0,
            'currency_id' => array_key_first($this->currencies),
        ]);

        actingAs($this->user);

        $response = patch(route('mentor-program.update', $anotherMentorProgram->slug), [
            'name'        => 'Updated Program Name',
            'description' => 'Updated Description',
            'cost'        => 150.0,
            'currency_id' => array_keys($this->currencies)[1],
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        $this->assertDatabaseHas('mentor_programs', [
            'id'          => $anotherMentorProgram->getKey(),
            'name'        => 'Another Program',
            'description' => 'Another Description',
            'cost'        => 75.0,
            'currency_id' => array_key_first($this->currencies),
        ]);
    });

    it('validates input when updating mentor program', function (): void {
        actingAs($this->user);

        $invalidData = [
            'name'        => '',
            'description' => '',
            'cost'        => -50,
            'currency_id' => null,
        ];

        $response = patch(route('mentor-program.update', $this->mentorProgram->slug), $invalidData);

        $response->assertSessionHasErrors(['name', 'description', 'cost', 'currency_id']);
    });

});
