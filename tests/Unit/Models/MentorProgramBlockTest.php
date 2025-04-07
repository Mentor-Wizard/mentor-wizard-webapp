<?php

declare(strict_types=1);

use App\Models\MentorProgram;
use App\Models\MentorProgramBlock;
use App\Models\MentorProgramBlockProgress;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\QueryException;

mutates(MentorProgramBlock::class);

describe('MentorProgramBlock Model', function () {
    beforeEach(function () {
        $this->seed(RoleSeeder::class);
    });

    it('has the correct fillable attributes', function () {
        $model = new MentorProgramBlock;

        expect($model->getFillable())->toEqual([
            'mentor_program_id',
            'name',
            'slug',
            'description',
        ]);
    });

    it('has a relationship with mentor program', function () {
        $mentorProgram = MentorProgram::factory()->create();

        expect($mentorProgram->mentor)->toBeInstanceOf(User::class);
    });

    it('has a relationship with mentiProgramProgress', function () {
        $mentiProgramProgress = MentorProgramBlockProgress::factory()->create();

        expect($mentiProgramProgress->menti)->toBeInstanceOf(User::class);
    });

    it('has precisely defined fillable attributes and mass assignment works correctly', function () {
        MentorProgramBlock::create(['extra_field' => 'test']);
    })->throws(MassAssignmentException::class);

    it('has correct casts for MentorProgramBlock', function () {
        $model = new MentorProgramBlock;

        expect($model->getCasts())->toEqual([
            'id' => 'int',
            'mentor_program_id' => 'int',
            'name' => 'string',
            'slug' => 'string',
            'description' => 'string',
        ]);
    });

    it('fails to create the model with duplicate slugs', function () {
        MentorProgramBlock::create([
            'mentor_program_id' => 1,
            'name' => 'some name',
            'description' => 'some description',
            'slug' => 'unique-slug',
        ]);

        $duplicateModel = MentorProgramBlock::create([
            'mentor_program_id' => 2,
            'name' => 'some name',
            'description' => 'some description',
            'slug' => 'unique-slug',
        ]);

        expect($duplicateModel->exists)->toBeFalse();
    })->throws(QueryException::class);
});
