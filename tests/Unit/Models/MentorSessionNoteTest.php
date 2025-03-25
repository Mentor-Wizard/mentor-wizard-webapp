<?php

declare(strict_types=1);

use App\Models\MentorSession;
use App\Models\MentorSessionNote;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\MassAssignmentException;

mutates(MentorSessionNote::class);

describe('MentorSessionNote Model', function () {
    beforeEach(function () {
        $this->seed(RoleSeeder::class);
        $this->mentor = User::factory()->create();
        $this->menti = User::factory()->create();

        $this->mentorSession = MentorSession::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
            'menti_id' => $this->menti->getKey(),
        ]);
    });

    it('can create session note with basic attributes with relations', function () {
        $mentorSessionNote = MentorSessionNote::factory()->create([
            'mentor_session_id' => $this->mentorSession->getKey(),
            'notes' => 'some notes',
        ]);

        expect($mentorSessionNote)->toBeInstanceOf(MentorSessionNote::class)
            ->and($mentorSessionNote->mentor_session_id)->toBe($this->mentorSession->getKey())
            ->and($mentorSessionNote->notes)->toBe('some notes')
            ->and($mentorSessionNote->mentorSession)->toBeInstanceOf(MentorSession::class)
            ->and($mentorSessionNote->mentorSession->getKey())->toBe($this->mentorSession->getKey());
    });

    it('can create session note with basic attributes and casts are correct', function () {
        $mentorSessionNote = MentorSessionNote::factory()->create([
            'mentor_session_id' => $this->mentorSession->getKey(),
            'notes' => 'some notes',
        ]);

        expect($mentorSessionNote)->toBeInstanceOf(MentorSessionNote::class)
            ->and($mentorSessionNote->mentor_session_id)->toBeInt()
            ->and($mentorSessionNote->notes)->toBe('some notes');
    });

    it('cascades on mentor session deletion', function () {
        $mentorSessionNote = MentorSessionNote::factory()->create([
            'mentor_session_id' => $this->mentorSession->getKey(),
            'notes' => 'some notes',
        ]);

        $this->mentorSession->delete();

        $this->assertDatabaseMissing('mentor_session_notes', [
            'id' => $mentorSessionNote->getKey(),
        ]);
    });

    it('has correctly defined fillable attributes', function () {
        $mentorSessionNote = new MentorSessionNote;

        expect($mentorSessionNote->getFillable())->toBe([
            'mentor_session_id',
            'notes',
        ]);
    });

    it('throws an exception when mass assigning unauthorized attributes', function () {
        $mentorSessionNote = new MentorSessionNote;

        $mentorSessionNote->fill([
            'mentor_session_id' => 123,
            'notes' => 'text',
            'extra_field' => 'unexpected',
        ]);
    })->throws(MassAssignmentException::class);

    it('has a precisely defined cast configuration', function () {
        $reflectionMethod = new ReflectionMethod(MentorSessionNote::class, 'casts');
        $mentorSessionNote = new MentorSessionNote;
        $casts = $reflectionMethod->invoke($mentorSessionNote);

        expect($casts)->toBe([
            'mentor_session_id' => 'int',
            'notes' => 'string',
        ]);
    });

    it('has precisely defined fillable attributes and mass assignment works correctly', function () {
        MentorSession::create(['extra_field' => 'test']);
    })->throws(MassAssignmentException::class);
});
