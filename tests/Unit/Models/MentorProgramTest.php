<?php

declare(strict_types=1);

use App\Models\MentorProgram;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\MassAssignmentException;

mutates(MentorProgram::class);

describe('MentorProgram Model', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->mentor = User::factory()->create();
    });

    it('has the correct fillable attributes', function (): void {
        $model = new MentorProgram;

        expect($model->getFillable())->toEqual([
            'mentor_id',
            'name',
            'slug',
            'description',
            'cost',
            'currency_id',
        ]);
    });

    it('has a relationship with mentor', function (): void {
        $mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
        ]);

        expect($mentorProgram->mentor)->toBeInstanceOf(User::class);
    });

    it('can create a mentor program using factory', function (): void {
        $mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
        ]);

        expect($mentorProgram)->toBeInstanceOf(MentorProgram::class)
            ->and($mentorProgram->exists)->toBeTrue();
    });

    it('has precisely defined fillable attributes and mass assignment works correctly', function (): void {
        \App\Models\MentorProgram::query()->create(['extra_field' => 'test']);
    })->throws(MassAssignmentException::class);
});
