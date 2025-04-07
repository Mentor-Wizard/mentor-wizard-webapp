<?php

declare(strict_types=1);

use App\Models\Chat;
use App\Models\MentorProgram;
use App\Models\MentorReview;
use App\Models\MentorSession;
use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

mutates(User::class);

describe('User Model', function () {
    beforeEach(function () {
        $this->seed(RoleSeeder::class);
    });

    it('can create a user', function () {
        $user = User::factory()->create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        expect($user)->toBeInstanceOf(User::class)
            ->and($user->username)->toBe('testuser')
            ->and($user->email)->toBe('test@example.com')
            ->and(Hash::check('password123', $user->password))->toBeTrue();
    });

    it('has a profile relationship', function () {
        $user = User::factory()->create();
        $profile = UserProfile::factory()->for($user)->create();

        expect($user->profile)->toBeInstanceOf(UserProfile::class)
            ->and($user->profile->getKey())->toBe($profile->getKey());
    });

    it('has mentor reviews relationship', function () {
        $mentor = User::factory()->create();
        $review = MentorReview::factory()->for($mentor, 'mentor')->create();

        expect($mentor->mentorReviews)->toHaveCount(1)
            ->and($mentor->mentorReviews->first()->getKey())->toBe($review->getKey());
    });

    it('has reviews by menti relationship', function () {
        $menti = User::factory()->create();
        $review = MentorReview::factory()->for($menti, 'menti')->create();

        expect($menti->reviewsByMenti)->toHaveCount(1)
            ->and($menti->reviewsByMenti->first()->getKey())->toBe($review->getKey());
    });

    it('has mentor programs relationship', function () {
        $mentor = User::factory()->create();
        $program = MentorProgram::factory()->create([
            'mentor_id' => $mentor->getKey(),
            'name' => 'Test Program',
        ]);

        expect($mentor->mentorPrograms)->toHaveCount(1)
            ->and($mentor->mentorPrograms->first()->getKey())->toBe($program->getKey());
    });

    it('has mentor and menti sessions relationships', function () {
        $mentor = User::factory()->create();
        $menti = User::factory()->create();
        $mentorSession = MentorSession::factory()->create([
            'mentor_id' => $mentor->getKey(),
            'menti_id' => $menti->getKey(),
        ]);

        expect($mentor->mentorSessions)->toHaveCount(1)
            ->and($menti->mentiSessions)->toHaveCount(1)
            ->and($mentor->mentorSessions->first()->getKey())->toBe($mentorSession->getKey())
            ->and($menti->mentiSessions->first()->getKey())->toBe($mentorSession->getKey());
    });

    it('has correct hidden attributes', function () {
        $user = User::factory()->create();
        $hiddenAttributes = $user->getHidden();

        expect($hiddenAttributes)->toContain('password')
            ->and($hiddenAttributes)->toContain('remember_token');
    });

    it('has a precisely defined cast configuration', function () {
        $reflectionMethod = new ReflectionMethod(User::class, 'casts');
        $user = new User;
        $casts = $reflectionMethod->invoke($user);

        expect($casts)->toBe([
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ]);
    });

    it('has precisely defined fillable attributes and mass assignment works correctly', function () {
        User::create(['extra_field' => 'test']);
    })->throws(MassAssignmentException::class);

    it('has coach chats relationship', function () {
        $coach = User::factory()->create();
        $menti = User::factory()->create();

        $chat = Chat::factory()->create([
            'coach_id' => $coach->getKey(),
            'menti_id' => $menti->getKey(),
        ]);

        expect($coach->coachChats)->toHaveCount(1)
            ->and($coach->coachChats->first()->getKey())->toBe($chat->getKey());
    });

    it('has menti chats relationship', function () {
        $mentor = User::factory()->create();
        $menti = User::factory()->create();

        $chat = Chat::factory()->create([
            'mentor_id' => $mentor->getKey(),
            'menti_id' => $menti->getKey(),
        ]);

        expect($menti->mentiChats)->toHaveCount(1)
            ->and($menti->mentiChats->first()->getKey())->toBe($chat->getKey());
    });

    it('has mentor chats relationship', function () {
        $mentor = User::factory()->create();
        $menti = User::factory()->create();

        $chat = Chat::factory()->create([
            'mentor_id' => $mentor->getKey(),
            'menti_id' => $menti->getKey(),
        ]);

        expect($mentor->mentorChats)->toHaveCount(1)
            ->and($mentor->mentorChats->first()->getKey())->toBe($chat->getKey());
    });

    it('returns empty collection when coach has no chats', function () {
        $coach = User::factory()->create();

        $coachChats = $coach->coachChats();

        expect($coachChats)->toBeInstanceOf(HasMany::class)
            ->and($coach->coachChats)->toHaveCount(0);
    });
});
