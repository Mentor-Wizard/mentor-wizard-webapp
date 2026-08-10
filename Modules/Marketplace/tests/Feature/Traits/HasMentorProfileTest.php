<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Modules\Marketplace\Models\MentorProfile;
use Modules\Marketplace\Models\MentorReview;
use Modules\Marketplace\Traits\HasMentorProfile;

describe('HasMentorProfile trait (post-move regression)', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    it('is used by App\Models\User', function (): void {
        expect(class_uses_recursive(User::class))->toHaveKey(HasMentorProfile::class);
    });

    it('resolves mentorProfile as a HasOne to Modules\Marketplace\Models\MentorProfile', function (): void {
        $mentor = User::factory()->create();
        $profile = MentorProfile::factory()->create(['user_id' => $mentor->getKey()]);

        $mentor->refresh();

        expect($mentor->mentorProfile)
            ->toBeInstanceOf(MentorProfile::class)
            ->getKey()->toBe($profile->getKey());
    });

    it('returns null mentorProfile when none exists', function (): void {
        $mentor = User::factory()->create();

        expect($mentor->mentorProfile)->toBeNull();
    });

    it('resolves mentorReviews as reviews received (mentor_id)', function (): void {
        $mentor = User::factory()->create();
        $menti = User::factory()->create();

        $review = MentorReview::factory()->create([
            'mentor_id' => $mentor->getKey(),
            'menti_id'  => $menti->getKey(),
            'rating'    => 4,
        ]);

        expect($mentor->mentorReviews)->toHaveCount(1)
            ->and($mentor->mentorReviews->first()->getKey())->toBe($review->getKey())
            ->and($menti->mentorReviews)->toBeEmpty();
    });

    it('resolves reviewsByMenti as reviews authored (menti_id)', function (): void {
        $mentor = User::factory()->create();
        $menti = User::factory()->create();

        $review = MentorReview::factory()->create([
            'mentor_id' => $mentor->getKey(),
            'menti_id'  => $menti->getKey(),
            'rating'    => 3,
        ]);

        expect($menti->reviewsByMenti)->toHaveCount(1)
            ->and($menti->reviewsByMenti->first()->getKey())->toBe($review->getKey())
            ->and($mentor->reviewsByMenti)->toBeEmpty();
    });

    it('computes rating as the average of received reviews', function (): void {
        $mentor = User::factory()->create();
        $mentiOne = User::factory()->create();
        $mentiTwo = User::factory()->create();

        MentorReview::factory()->create([
            'mentor_id' => $mentor->getKey(),
            'menti_id'  => $mentiOne->getKey(),
            'rating'    => 5,
        ]);
        MentorReview::factory()->create([
            'mentor_id' => $mentor->getKey(),
            'menti_id'  => $mentiTwo->getKey(),
            'rating'    => 3,
        ]);

        expect($mentor->rating)->toBe(4.0);
    });

    it('returns zero rating when the mentor has no reviews', function (): void {
        $mentor = User::factory()->create();

        expect($mentor->rating)->toBe(0.0);
    });
});
