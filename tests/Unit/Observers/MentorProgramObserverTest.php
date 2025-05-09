<?php

declare(strict_types=1);

use App\Models\MentorProgram;
use App\Observers\MentorProgramObserver;
use Database\Seeders\RoleSeeder;

describe('MentorProgramObserver', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->mentorProgramObserver = new MentorProgramObserver;
    });

    it('generates a unique slug when saved', function (): void {
        $mentorProgram = MentorProgram::factory()->create([
            'name' => 'Test Program',
            'slug' => null,
        ]);

        $this->mentorProgramObserver->saved($mentorProgram);

        expect($mentorProgram->slug)->toBe('test-program');
    });

    it('increments slug if it already exists', function (): void {
        MentorProgram::factory()->create([
            'name' => 'Test Program',
            'slug' => 'test-program',
        ]);

        $mentorProgram = MentorProgram::factory()->create([
            'name' => 'Test Program',
            'slug' => null,
        ]);

        $this->mentorProgramObserver->saved($mentorProgram);

        expect($mentorProgram->slug)->toMatch('/^test-program-\d+$/');
    });
});
