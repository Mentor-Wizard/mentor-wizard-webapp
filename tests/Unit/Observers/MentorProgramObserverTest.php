<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Models\MentorProgram;
use App\Models\User;
use App\Observers\MentorProgramObserver;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;

describe('MentorProgramObserver', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->mentorProgramObserver = new MentorProgramObserver;
        $this->mentor = User::factory()->create();
        $this->mentor->assignRole(Role::findByName(RoleEnum::MENTOR->value));
    });

    it('generates a unique slug when saved', function (): void {
        $mentorProgram = MentorProgram::factory()->create([
            'name'      => 'Test Program',
            'slug'      => null,
            'mentor_id' => $this->mentor->getKey(),
        ]);

        $this->mentorProgramObserver->saved($mentorProgram);

        expect($mentorProgram->slug)->toBe('test-program');
    });

    it('increments slug if it already exists', function (): void {
        MentorProgram::factory()->create([
            'name'      => 'Test Program',
            'slug'      => 'test-program',
            'mentor_id' => $this->mentor->getKey(),
        ]);

        $mentorProgram = MentorProgram::factory()->create([
            'name'      => 'Test Program',
            'slug'      => null,
            'mentor_id' => $this->mentor->getKey(),
        ]);

        $this->mentorProgramObserver->saved($mentorProgram);

        expect($mentorProgram->slug)->toMatch('/^test-program-\d+$/');
    });
});
