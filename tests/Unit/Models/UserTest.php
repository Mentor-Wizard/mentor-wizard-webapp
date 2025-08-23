<?php

declare(strict_types=1);

use App\Models\Chat;
use App\Models\MentorProfile;
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

describe('User Model', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    it('can create a user', function (): void {
        $user = User::factory()->create([
            'username' => 'testuser',
            'email'    => 'test@example.com',
            'password' => 'password123',
        ]);

        expect($user)->toBeInstanceOf(User::class)
            ->and($user->username)->toBe('testuser')
            ->and($user->email)->toBe('test@example.com')
            ->and(Hash::check('password123', $user->password))->toBeTrue();
    });

    it('has a profile relationship', function (): void {
        $user = User::factory()->create();
        $profile = UserProfile::factory()->for($user)->create();

        expect($user->profile)->toBeInstanceOf(UserProfile::class);
    });

    it('has a mentor relationship', function (): void {
        $user = User::factory()->create();
        MentorProfile::factory()->for($user)->create();

        expect($user->mentorProfile)->toBeInstanceOf(MentorProfile::class);
    });

    it('has mentor reviews relationship', function (): void {
        $mentor = User::factory()->create();
        $review = MentorReview::factory()->for($mentor, 'mentor')->create();

        expect($mentor->mentorReviews)->toHaveCount(1)
            ->and($mentor->mentorReviews->first()->getKey())->toBe($review->getKey());
    });

    it('has reviews by menti relationship', function (): void {
        $menti = User::factory()->create();
        $review = MentorReview::factory()->for($menti, 'menti')->create();

        expect($menti->reviewsByMenti)->toHaveCount(1)
            ->and($menti->reviewsByMenti->first()->getKey())->toBe($review->getKey());
    });

    it('has mentor programs relationship', function (): void {
        $mentor = User::factory()->create();
        $program = MentorProgram::factory()->create([
            'mentor_id' => $mentor->getKey(),
            'name'      => 'Test Program',
        ]);

        expect($mentor->mentorPrograms)->toHaveCount(1)
            ->and($mentor->mentorPrograms->first()->getKey())->toBe($program->getKey());
    });

    it('has mentor and menti sessions relationships', function (): void {
        $mentor = User::factory()->create();
        $menti = User::factory()->create();
        $mentorSession = MentorSession::factory()->create([
            'mentor_id' => $mentor->getKey(),
            'menti_id'  => $menti->getKey(),
        ]);

        expect($mentor->mentorSessions)->toHaveCount(1)
            ->and($menti->mentiSessions)->toHaveCount(1)
            ->and($mentor->mentorSessions->first()->getKey())->toBe($mentorSession->getKey())
            ->and($menti->mentiSessions->first()->getKey())->toBe($mentorSession->getKey());
    });

    it('has correct hidden attributes', function (): void {
        $user = User::factory()->create();
        $hiddenAttributes = $user->getHidden();

        expect($hiddenAttributes)->toContain('password')
            ->and($hiddenAttributes)->toContain('remember_token');
    });

    it('has a precisely defined cast configuration', function (): void {
        $reflectionMethod = new ReflectionMethod(User::class, 'casts');
        $user = new User;
        $casts = $reflectionMethod->invoke($user);

        expect($casts)->toBe([
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ]);
    });

    it('has precisely defined fillable attributes and mass assignment works correctly', function (): void {
        User::query()->create(['extra_field' => 'test']);
    })->throws(MassAssignmentException::class);

    it('has coach chats relationship', function (): void {
        $coach = User::factory()->create();
        $menti = User::factory()->create();

        $chat = Chat::factory()->create([
            'coach_id' => $coach->getKey(),
            'menti_id' => $menti->getKey(),
        ]);

        expect($coach->coachChats)->toHaveCount(1)
            ->and($coach->coachChats->first()->getKey())->toBe($chat->getKey());
    });

    it('has menti chats relationship', function (): void {
        $mentor = User::factory()->create();
        $menti = User::factory()->create();

        $chat = Chat::factory()->create([
            'mentor_id' => $mentor->getKey(),
            'menti_id'  => $menti->getKey(),
        ]);

        expect($menti->mentiChats)->toHaveCount(1)
            ->and($menti->mentiChats->first()->getKey())->toBe($chat->getKey());
    });

    it('has mentor chats relationship', function (): void {
        $mentor = User::factory()->create();
        $menti = User::factory()->create();

        $chat = Chat::factory()->create([
            'mentor_id' => $mentor->getKey(),
            'menti_id'  => $menti->getKey(),
        ]);

        expect($mentor->mentorChats)->toHaveCount(1)
            ->and($mentor->mentorChats->first()->getKey())->toBe($chat->getKey());
    });

    it('returns empty collection when coach has no chats', function (): void {
        $coach = User::factory()->create();

        $coachChats = $coach->coachChats();

        expect($coachChats)->toBeInstanceOf(HasMany::class)
            ->and($coach->coachChats)->toHaveCount(0);
    });

    it('has menti program progress relationship', function (): void {
        $menti = User::factory()->create();

        $relationship = $menti->mentiProgramProgress();

        expect($relationship)->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\HasOne::class);
    });

    it('returns correct filament name', function (): void {
        $user = User::factory()->create(['username' => 'testuser']);

        expect($user->getFilamentName())->toBe('testuser');
    });

    it('returns empty string when username is null for filament name', function (): void {
        $user = new User;
        $user->username = null;

        expect($user->getFilamentName())->toBe('');
    });
    it('returns available slots correctly', function (): void {
        $user = User::factory()->create();

        $slots = $user->getAvailableSlots();

        expect($slots)->toBeArray();
    });
});
