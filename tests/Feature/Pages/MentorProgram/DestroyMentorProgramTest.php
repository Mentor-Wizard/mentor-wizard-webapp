<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Enums\RoleGuardEnum;
use App\Models\MentorProgram;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;

beforeEach(function (): void {
    DB::statement('ALTER SEQUENCE currencies_id_seq RESTART WITH 1');
    $this->seed(CurrencySeeder::class);
    $this->seed(RoleSeeder::class);

    $this->user = User::factory()->create();
    $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value, RoleGuardEnum::MENTOR->value));

    $this->mentorProgram = MentorProgram::factory()->create([
        'mentor_id'   => $this->user->id,
        'name'        => 'Test Program',
        'slug'        => 'test-program',
        'description' => 'Test Description',
        'cost'        => 100.0,
        'currency_id' => 1,
    ]);
});

it('deletes mentor program successfully', function (): void {
    actingAs($this->user);

    $response = delete(route('mentor-program.destroy', $this->mentorProgram->slug));

    $response->assertRedirect(route('mentor-program.create'));

    $this->assertDatabaseMissing('mentor_programs', [
        'id' => $this->mentorProgram->id,
    ]);
});

it('throws 404 when trying to delete a non-existent mentor program', function (): void {
    actingAs($this->user);

    $nonExistentSlug = 'non-existent-slug';

    $response = delete(route('mentor-program.destroy', $nonExistentSlug));

    $response->assertStatus(Response::HTTP_NOT_FOUND);
});

it("throws 403 when trying to delete another mentor's program", function (): void {
    $anotherUser = User::factory()->create();
    $anotherUser->assignRole(Role::findByName(RoleEnum::MENTOR->value, RoleGuardEnum::MENTOR->value));

    $anotherMentorProgram = MentorProgram::factory()->create([
        'mentor_id'   => $anotherUser->id,
        'name'        => 'Another Program',
        'slug'        => 'another-program',
        'description' => 'Another Description',
        'cost'        => 75.0,
        'currency_id' => 1,
    ]);

    actingAs($this->user);

    $response = delete(route('mentor-program.destroy', $anotherMentorProgram->slug));

    $response->assertStatus(Response::HTTP_FORBIDDEN);

    $this->assertDatabaseHas('mentor_programs', [
        'id' => $anotherMentorProgram->id,
    ]);
});
