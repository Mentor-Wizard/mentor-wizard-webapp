<?php

declare(strict_types=1);

use App\Models\MentorProgram;
use App\Models\MentorReview;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\MassAssignmentException;

mutates(MentorReview::class);

describe('MentorReview Model', function () {
    beforeEach(function () {
        $this->seed(RoleSeeder::class);
    });

    it('has the correct fillable attributes', function () {
        $model = new MentorReview;

        expect($model->getFillable())->toEqual([
            'mentor_id',
            'menti_id',
            'comment',
            'rating',
        ]);
    });

    it('has a relationship with mentor', function () {
        $mentorReview = MentorReview::factory()->create();

        expect($mentorReview->mentor)->toBeInstanceOf(User::class);
    });

    it('has a relationship with menti', function () {
        $mentorReview = MentorReview::factory()->create();

        expect($mentorReview->mentor)->toBeInstanceOf(User::class);
    });

    it('can create a mentor review using factory', function () {
        $mentorReview = MentorReview::factory()->create();

        expect($mentorReview)->toBeInstanceOf(MentorReview::class)
            ->and($mentorReview->exists)->toBeTrue();
    });

    it('has precisely defined fillable attributes and mass assignment works correctly', function () {
        MentorProgram::create(['extra_field' => 'test']);
    })->throws(MassAssignmentException::class);
});
