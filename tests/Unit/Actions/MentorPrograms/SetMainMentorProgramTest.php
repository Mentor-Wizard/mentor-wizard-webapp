<?php

declare(strict_types=1);

use App\Actions\MentorPrograms\SetMainMentorProgram;
use App\Enums\RoleEnum;
use App\Models\MentorProgram;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

mutates(SetMainMentorProgram::class);

describe('SetMainMentorProgram', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        $this->mentor = User::factory()->create();
        $this->mentor->assignRole(Role::findByName(RoleEnum::MENTOR->value));
    });

    it('sets the given program as main and unsets all others', function (): void {
        $main = MentorProgram::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
            'is_main'   => true,
        ]);
        $other = MentorProgram::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
            'is_main'   => false,
        ]);

        $response = (new SetMainMentorProgram)->handle($other);

        expect($response)
            ->toBeInstanceOf(RedirectResponse::class)
            ->and($response->getSession()->get('success'))->toBe('Main consultation updated successfully.');

        expect($main->fresh()->is_main)->toBeFalse();
        expect($other->fresh()->is_main)->toBeTrue();
    });

    it('redirects with error when a database exception occurs', function (): void {
        $program = MentorProgram::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
        ]);

        DB::shouldReceive('beginTransaction')->once()->andThrow(new Exception('DB error'));
        DB::shouldReceive('rollBack')->once();

        $response = (new SetMainMentorProgram)->handle($program);

        expect($response)
            ->toBeInstanceOf(RedirectResponse::class)
            ->and($response->getSession()->get('error'))->toBe('Error with setting main consultation.');
    });
});
