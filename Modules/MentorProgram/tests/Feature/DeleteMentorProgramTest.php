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

describe('Mentor Program Destroy', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->seed(CurrencySeeder::class);
        $this->currencies = Currency::query()->pluck('name', 'id')->toArray();

        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id'   => $this->user->getKey(),
            'name'        => 'Test Program',
            'slug'        => 'test-program',
            'description' => 'Test Description',
            'cost'        => 100.0,
            'currency_id' => array_key_first($this->currencies),
        ]);
    });

    it('deletes mentor program successfully', function (): void {
        actingAs($this->user);

        $response = $this->withSession(['_token' => 'test token'])
            ->delete(route('mentor-program.destroy', [$this->mentorProgram->slug,
                '_token' => 'test token']));

        $response->assertRedirect(route('mentor-program.list'));

        $this->assertDatabaseMissing('mentor_programs', [
            'id' => $this->mentorProgram->getKey(),
        ]);
    });

    it('throws 404 when trying to delete a non-existent mentor program', function (): void {
        actingAs($this->user);

        $nonExistentSlug = 'non-existent-slug';

        $response = $this->withSession(['_token' => 'test token'])
            ->delete(route('mentor-program.destroy',
                [
                    $nonExistentSlug,
                    '_token' => 'test token',
                ]));

        $response->assertNotFound();
    });

    it("throws 403 when trying to delete another mentor's program", function (): void {
        $anotherUser = User::factory()->create();
        $anotherUser->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $anotherMentorProgram = MentorProgram::factory()->create([
            'mentor_id'   => $anotherUser->getKey(),
            'name'        => 'Another Program',
            'slug'        => 'another-program',
            'description' => 'Another Description',
            'cost'        => 75.0,
            'currency_id' => array_key_first($this->currencies),
        ]);

        actingAs($this->user);

        $response = $this->withSession(['_token' => 'test token'])
            ->delete(route('mentor-program.destroy',
                [
                    $anotherMentorProgram->slug,
                    '_token' => 'test token',
                ]
            ));

        $response->assertForbidden();

        $this->assertDatabaseHas('mentor_programs', [
            'id' => $anotherMentorProgram->getKey(),
        ]);
    });

});
