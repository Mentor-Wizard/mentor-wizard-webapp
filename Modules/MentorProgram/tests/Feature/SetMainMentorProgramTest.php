<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Models\Currency;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\RoleSeeder;
use Modules\MentorProgram\Models\MentorProgram;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

describe('Set Main Mentor Program', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->seed(CurrencySeeder::class);
        $this->currencies = Currency::query()->pluck('name', 'id')->toArray();

        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $this->mainProgram = MentorProgram::factory()->create([
            'mentor_id'   => $this->user->getKey(),
            'name'        => 'Main Program',
            'is_main'     => true,
            'currency_id' => array_key_first($this->currencies),
        ]);

        $this->otherProgram = MentorProgram::factory()->create([
            'mentor_id'   => $this->user->getKey(),
            'name'        => 'Other Program',
            'is_main'     => false,
            'currency_id' => array_key_first($this->currencies),
        ]);
    });

    it('sets the program as main and unsets all others', function (): void {
        actingAs($this->user);

        $response = patch(route('mentor-program.set-main', $this->otherProgram->slug));

        $response->assertRedirect(route('mentor-program.edit', $this->otherProgram->slug));

        $this->assertDatabaseHas('mentor_programs', [
            'id'      => $this->otherProgram->getKey(),
            'is_main' => true,
        ]);

        $this->assertDatabaseHas('mentor_programs', [
            'id'      => $this->mainProgram->getKey(),
            'is_main' => false,
        ]);
    });

    it('flashes a success message after setting as main', function (): void {
        actingAs($this->user);

        patch(route('mentor-program.set-main', $this->otherProgram->slug))
            ->assertSessionHas('success', 'Main consultation updated successfully.');
    });

    it('sets program as main when it is the only program', function (): void {
        actingAs($this->user);

        $this->mainProgram->delete();

        $response = patch(route('mentor-program.set-main', $this->otherProgram->slug));

        $response->assertRedirect(route('mentor-program.edit', $this->otherProgram->slug));

        $this->assertDatabaseHas('mentor_programs', [
            'id'      => $this->otherProgram->getKey(),
            'is_main' => true,
        ]);
    });

    it('throws 403 when another mentor tries to set main', function (): void {
        $anotherMentor = User::factory()->create();
        $anotherMentor->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        actingAs($anotherMentor);

        $response = patch(route('mentor-program.set-main', $this->otherProgram->slug));

        $response->assertForbidden();

        $this->assertDatabaseHas('mentor_programs', [
            'id'      => $this->otherProgram->getKey(),
            'is_main' => false,
        ]);
    });

    it('returns 404 for non-existent program slug', function (): void {
        actingAs($this->user);

        $response = patch(route('mentor-program.set-main', 'non-existent-slug'));

        $response->assertNotFound();
    });

    it('redirects unauthenticated user to login', function (): void {
        $response = patch(route('mentor-program.set-main', $this->otherProgram->slug));

        $response->assertRedirect(route('login'));
    });
});
