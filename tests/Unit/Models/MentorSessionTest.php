<?php

declare(strict_types=1);

use App\Models\MentorSession;
use App\Models\MentorSessionNote;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

mutates(MentorSession::class);

describe('MentorSession Model', function () {
    beforeEach(function () {
        $this->seed(RoleSeeder::class);
        $this->mentor = User::factory()->create();
        $this->menti = User::factory()->create();
    });

    it('can create session with basic attributes with relations', function () {
        $mentorSession = MentorSession::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
            'menti_id' => $this->menti->getKey(),
            'date' => now(),
            'cost' => 99.99,
            'is_success' => true,
            'is_paid' => true,
            'is_cancelled' => false,
            'is_date_changed' => false,
        ]);

        expect($mentorSession)->toBeInstanceOf(MentorSession::class)
            ->and($mentorSession->mentor_id)->toBe($this->mentor->getKey())
            ->and($mentorSession->menti_id)->toBe($this->menti->getKey())
            ->and($mentorSession->cost)->toBe(99.99)
            ->and($mentorSession->is_success)->toBeTrue()
            ->and($mentorSession->is_paid)->toBeTrue()
            ->and($mentorSession->is_cancelled)->toBeFalse()
            ->and($mentorSession->is_date_changed)->toBeFalse()
            ->and($mentorSession->date)->toBeInstanceOf(DateTime::class)
            ->and($mentorSession->mentor)->toBeInstanceOf(User::class)
            ->and($mentorSession->mentor->getKey())->toBe($this->mentor->getKey())
            ->and($mentorSession->menti)->toBeInstanceOf(User::class)
            ->and($mentorSession->menti->getKey())->toBe($this->menti->getKey());
    });

    it('can create session with basic attributes and casts are correct', function () {
        $mentorSession = MentorSession::factory()->create([
            'mentor_id' => (string) $this->mentor->getKey(),
            'menti_id' => (string) $this->menti->getKey(),
            'date' => '2024-02-10 15:00:00',
            'cost' => '99.99',
            'is_success' => 1,
            'is_paid' => 1,
            'is_cancelled' => 0,
            'is_date_changed' => 0,
        ]);

        expect($mentorSession)->toBeInstanceOf(MentorSession::class)
            ->and($mentorSession->mentor_id)->toBeInt()
            ->and($mentorSession->menti_id)->toBeInt()
            ->and($mentorSession->cost)->toBe(99.99)
            ->and($mentorSession->is_success)->toBeTrue()
            ->and($mentorSession->is_paid)->toBeTrue()
            ->and($mentorSession->is_cancelled)->toBeFalse()
            ->and($mentorSession->is_date_changed)->toBeFalse()
            ->and($mentorSession->date)->toBeInstanceOf(DateTime::class);
    });

    it('cascades on mentor deletion', function () {
        $mentorSession = MentorSession::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
            'menti_id' => $this->menti->getKey(),
        ]);

        $this->mentor->delete();

        $this->assertDatabaseMissing('mentor_sessions', [
            'id' => $mentorSession->getKey(),
        ]);
    });

    it('cascades on menti deletion', function () {
        $mentorSession = MentorSession::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
            'menti_id' => $this->menti->getKey(),
        ]);

        $this->menti->delete();

        $this->assertDatabaseMissing('mentor_sessions', [
            'id' => $mentorSession->getKey(),
        ]);
    });

    it('has correctly defined fillable attributes', function () {
        $mentorSession = new MentorSession;

        expect($mentorSession->getFillable())->toBe([
            'mentor_id',
            'menti_id',
            'date',
            'is_success',
            'is_paid',
            'is_cancelled',
            'is_date_changed',
            'cost',
        ]);
    });

    it('throws an exception when mass assigning unauthorized attributes', function () {
        $mentorSession = new MentorSession;

        $mentorSession->fill([
            'mentor_id' => 123,
            'menti_id' => 456,
            'date' => '2024-03-03 12:00:00',
            'is_success' => 1,
            'is_paid' => 0,
            'is_cancelled' => 1,
            'is_date_changed' => 1,
            'cost' => '150.75',
            'extra_field' => 'unexpected',
        ]);
    })->throws(MassAssignmentException::class);

    it('has a precisely defined cast configuration', function () {
        $reflectionMethod = new ReflectionMethod(MentorSession::class, 'casts');
        $mentorSession = new MentorSession;
        $casts = $reflectionMethod->invoke($mentorSession);

        expect($casts)->toBe([
            'mentor_id' => 'int',
            'menti_id' => 'int',
            'date' => 'datetime',
            'is_success' => 'boolean',
            'is_paid' => 'boolean',
            'is_cancelled' => 'boolean',
            'is_date_changed' => 'boolean',
            'cost' => 'float',
        ]);
    });

    it('has precisely defined fillable attributes and mass assignment works correctly', function () {
        MentorSession::create(['extra_field' => 'test']);
    })->throws(MassAssignmentException::class);

    it('has a valid mentorSessionNote relation', function () {
        $mentorSession = MentorSession::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
            'menti_id' => $this->menti->getKey(),
        ]);

        $mentorSessionNote = MentorSessionNote::factory()->create([
            'mentor_session_id' => $mentorSession->getKey(),
            'notes' => 'some notes',
        ]);

        expect($mentorSession->mentorSessionNote)->toBeInstanceOf(MentorSessionNote::class)
            ->and($mentorSession->mentorSessionNote->getKey())->toBe($mentorSessionNote->getKey());
    });

    it('throws MassAssignmentException when trying to fill non-fillable attributes', function () {
        expect(function () {
            MentorSession::create([
                'mentor_id' => $this->mentor->getKey(),
                'menti_id' => $this->menti->getKey(),
                'nonexistent_attribute' => 'test value',
            ]);
        })->toThrow(MassAssignmentException::class);
    });

    it('has correctly defined relations', function () {
        $mentorSession = MentorSession::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
            'menti_id' => $this->menti->getKey(),
        ]);

        expect($mentorSession->mentor())->toBeInstanceOf(BelongsTo::class)
            ->and($mentorSession->menti())->toBeInstanceOf(BelongsTo::class)
            ->and($mentorSession->payment())->toBeInstanceOf(HasOne::class)
            ->and($mentorSession->mentorSessionNote())->toBeInstanceOf(HasOne::class);
    });

    it('can create session with MentorSessionNote', function () {
        $mentorSession = MentorSession::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
            'menti_id' => $this->menti->getKey(),
        ]);

        $mentorSessionNote = MentorSessionNote::factory()->create([
            'mentor_session_id' => $mentorSession->getKey(),
        ]);

        expect($mentorSession->mentorSessionNote)->toBeInstanceOf(MentorSessionNote::class)
            ->and($mentorSession->mentorSessionNote->getKey())->toBe($mentorSessionNote->getKey());
    });

    it('can create session with Payment', function () {
        $mentorSession = MentorSession::factory()->create([
            'mentor_id' => $this->mentor->getKey(),
            'menti_id' => $this->menti->getKey(),
        ]);

        $payment = Payment::factory()->create([
            'mentor_session_id' => $mentorSession->getKey(),
        ]);

        expect($mentorSession->payment)->toBeInstanceOf(Payment::class)
            ->and($mentorSession->payment->getKey())->toBe($payment->getKey());
    });
});
