<?php

declare(strict_types=1);

use App\Models\MentorProgram;
use App\Models\MentorProgramBlock;
use App\Models\MentorProgramBlockProgress;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\QueryException;

mutates(MentorProgramBlockProgress::class);

describe('MentorProgramBlockProgress Model', function () {
    beforeEach(function () {
        $this->seed(RoleSeeder::class);
    });

    it('has the correct fillable attributes', function () {
        $model = new MentorProgramBlockProgress;
        expect($model->getFillable())->toEqual([
            'mentor_program_block_id',
            'menti_id',
            'is_completed',
        ]);
    });

    it('has correct casts for MentorProgramBlockProgress', function () {
        $model = new MentorProgramBlockProgress;
        expect($model->getCasts())->toEqual([
            'id' => 'int',
            'mentor_program_block_id' => 'int',
            'menti_id' => 'int',
            'is_completed' => 'boolean',
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

    it('fails to create the model without required fields', function () {
        MentorProgramBlockProgress::create(['mentor_program_block_id' => null, 'menti_id' => null]);
    })->throws(QueryException::class);
});
