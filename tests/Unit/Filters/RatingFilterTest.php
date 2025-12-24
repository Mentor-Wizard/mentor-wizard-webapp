<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Filters\RatingFilter;
use App\Models\MentorProfile;
use App\Models\MentorReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\QueryBuilder\Filters\Filter;

uses(RefreshDatabase::class);
covers(RatingFilter::class);

describe('RatingFilter', function (): void {
    beforeEach(function (): void {
        // Create required role for UserObserver
        Role::create(['name' => RoleEnum::USER->value]);

        $this->filter = new RatingFilter;
    });

    it('implements Filter interface', function (): void {
        expect($this->filter)->toBeInstanceOf(Filter::class);
    });

    it('has correct __invoke method signature', function (): void {
        $reflection = new ReflectionMethod($this->filter, '__invoke');

        expect($reflection->getNumberOfParameters())->toBe(3)
            ->and($reflection->isPublic())->toBeTrue();

        $params = $reflection->getParameters();
        expect($params[0]->getName())->toBe('query')
            ->and($params[1]->getName())->toBe('value')
            ->and($params[2]->getName())->toBe('property');
    });

    it('can be instantiated without dependencies', function (): void {
        $reflection = new ReflectionClass(RatingFilter::class);
        $constructor = $reflection->getConstructor();

        if ($constructor !== null) {
            expect($constructor->getNumberOfRequiredParameters())->toBe(0);
        }

        expect(new RatingFilter)->toBeInstanceOf(RatingFilter::class);
    });

    describe('basic rating filtering', function (): void {
        it('filters mentors with rating >= 4', function (): void {
            $user1 = User::factory()->create();
            $profile1 = MentorProfile::factory()->create(['user_id' => $user1->id]);
            MentorReview::factory()->create(['mentor_id' => $user1->id, 'rating' => 5]);

            $user2 = User::factory()->create();
            $profile2 = MentorProfile::factory()->create(['user_id' => $user2->id]);
            MentorReview::factory()->create(['mentor_id' => $user2->id, 'rating' => 3]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 4, 'rating');
            $results = $query->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($profile1->id)
                ->and($results->pluck('id')->toArray())->toContain($profile1->id)
                ->and($results->pluck('id')->toArray())->not->toContain($profile2->id);
        });

        it('filters mentors with rating >= 3', function (): void {
            $user1 = User::factory()->create();
            $profile1 = MentorProfile::factory()->create(['user_id' => $user1->id]);
            MentorReview::factory()->create(['mentor_id' => $user1->id, 'rating' => 4]);

            $user2 = User::factory()->create();
            $profile2 = MentorProfile::factory()->create(['user_id' => $user2->id]);
            MentorReview::factory()->create(['mentor_id' => $user2->id, 'rating' => 3]);

            $user3 = User::factory()->create();
            $profile3 = MentorProfile::factory()->create(['user_id' => $user3->id]);
            MentorReview::factory()->create(['mentor_id' => $user3->id, 'rating' => 2]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 3, 'rating');
            $results = $query->get();

            expect($results)->toHaveCount(2)
                ->and($results->pluck('id')->toArray())->toContain($profile1->id)
                ->and($results->pluck('id')->toArray())->toContain($profile2->id)
                ->and($results->pluck('id')->toArray())->not->toContain($profile3->id);
        });

        it('filters mentors with rating >= 5', function (): void {
            $user1 = User::factory()->create();
            $profile1 = MentorProfile::factory()->create(['user_id' => $user1->id]);
            MentorReview::factory()->create(['mentor_id' => $user1->id, 'rating' => 5]);

            $user2 = User::factory()->create();
            $profile2 = MentorProfile::factory()->create(['user_id' => $user2->id]);
            MentorReview::factory()->create(['mentor_id' => $user2->id, 'rating' => 4]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 5, 'rating');
            $results = $query->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($profile1->id)
                ->and($results->pluck('id')->toArray())->not->toContain($profile2->id);
        });

        it('excludes mentors with no reviews', function (): void {
            $user1 = User::factory()->create();
            $profile1 = MentorProfile::factory()->create(['user_id' => $user1->id]);
            MentorReview::factory()->create(['mentor_id' => $user1->id, 'rating' => 4]);

            $user2 = User::factory()->create();
            $profile2 = MentorProfile::factory()->create(['user_id' => $user2->id]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 3, 'rating');
            $results = $query->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($profile1->id)
                ->and($results->pluck('id')->toArray())->not->toContain($profile2->id);
        });
    });

    describe('average rating calculation', function (): void {
        it('calculates average from multiple reviews correctly when >= threshold', function (): void {
            $user = User::factory()->create();
            $profile = MentorProfile::factory()->create(['user_id' => $user->id]);

            MentorReview::factory()->create(['mentor_id' => $user->id, 'rating' => 5]);
            MentorReview::factory()->create(['mentor_id' => $user->id, 'rating' => 4]);
            MentorReview::factory()->create(['mentor_id' => $user->id, 'rating' => 3]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 4, 'rating');
            $results = $query->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($profile->id);
        });

        it('excludes mentors when average is below threshold', function (): void {
            $user = User::factory()->create();
            $profile = MentorProfile::factory()->create(['user_id' => $user->id]);

            MentorReview::factory()->create(['mentor_id' => $user->id, 'rating' => 3]);
            MentorReview::factory()->create(['mentor_id' => $user->id, 'rating' => 2]);
            MentorReview::factory()->create(['mentor_id' => $user->id, 'rating' => 1]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 4, 'rating');
            $results = $query->get();

            expect($results)->toHaveCount(0)
                ->and($results->pluck('id')->toArray())->not->toContain($profile->id);
        });

        it('handles average rating of exactly 4.0 with threshold 4', function (): void {
            $user = User::factory()->create();
            $profile = MentorProfile::factory()->create(['user_id' => $user->id]);

            MentorReview::factory()->create(['mentor_id' => $user->id, 'rating' => 5]);
            MentorReview::factory()->create(['mentor_id' => $user->id, 'rating' => 3]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 4, 'rating');
            $results = $query->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($profile->id);
        });

        it('handles average rating of exactly 3.5 with threshold 3.5', function (): void {
            $user = User::factory()->create();
            $profile = MentorProfile::factory()->create(['user_id' => $user->id]);

            MentorReview::factory()->create(['mentor_id' => $user->id, 'rating' => 4]);
            MentorReview::factory()->create(['mentor_id' => $user->id, 'rating' => 3]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 3.5, 'rating');
            $results = $query->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($profile->id);
        });

        it('excludes mentors with average slightly below threshold', function (): void {
            $user = User::factory()->create();
            $profile = MentorProfile::factory()->create(['user_id' => $user->id]);

            MentorReview::factory()->create(['mentor_id' => $user->id, 'rating' => 4]);
            MentorReview::factory()->create(['mentor_id' => $user->id, 'rating' => 3]);
            MentorReview::factory()->create(['mentor_id' => $user->id, 'rating' => 3]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 4, 'rating');
            $results = $query->get();

            expect($results)->toHaveCount(0)
                ->and($results->pluck('id')->toArray())->not->toContain($profile->id);
        });

        it('includes mentor with single review matching threshold', function (): void {
            $user = User::factory()->create();
            $profile = MentorProfile::factory()->create(['user_id' => $user->id]);

            MentorReview::factory()->create(['mentor_id' => $user->id, 'rating' => 4]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 4, 'rating');
            $results = $query->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($profile->id);
        });

        it('excludes mentor with single review below threshold', function (): void {
            $user = User::factory()->create();
            $profile = MentorProfile::factory()->create(['user_id' => $user->id]);

            MentorReview::factory()->create(['mentor_id' => $user->id, 'rating' => 3]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 4, 'rating');
            $results = $query->get();

            expect($results)->toHaveCount(0)
                ->and($results->pluck('id')->toArray())->not->toContain($profile->id);
        });
    });

    describe('decimal rating thresholds', function (): void {
        it('filters with threshold 4.5', function (): void {
            $user1 = User::factory()->create();
            $profile1 = MentorProfile::factory()->create(['user_id' => $user1->id]);
            MentorReview::factory()->create(['mentor_id' => $user1->id, 'rating' => 5]);
            MentorReview::factory()->create(['mentor_id' => $user1->id, 'rating' => 4]);

            $user2 = User::factory()->create();
            $profile2 = MentorProfile::factory()->create(['user_id' => $user2->id]);
            MentorReview::factory()->create(['mentor_id' => $user2->id, 'rating' => 4]);
            MentorReview::factory()->create(['mentor_id' => $user2->id, 'rating' => 4]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 4.5, 'rating');
            $results = $query->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($profile1->id)
                ->and($results->pluck('id')->toArray())->not->toContain($profile2->id);
        });

        it('filters with threshold 3.7', function (): void {
            $user1 = User::factory()->create();
            $profile1 = MentorProfile::factory()->create(['user_id' => $user1->id]);
            MentorReview::factory()->create(['mentor_id' => $user1->id, 'rating' => 4]);
            MentorReview::factory()->create(['mentor_id' => $user1->id, 'rating' => 4]);

            $user2 = User::factory()->create();
            $profile2 = MentorProfile::factory()->create(['user_id' => $user2->id]);
            MentorReview::factory()->create(['mentor_id' => $user2->id, 'rating' => 3]);
            MentorReview::factory()->create(['mentor_id' => $user2->id, 'rating' => 3]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 3.7, 'rating');
            $results = $query->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($profile1->id)
                ->and($results->pluck('id')->toArray())->not->toContain($profile2->id);
        });

        it('handles string decimal threshold conversion', function (): void {
            $user = User::factory()->create();
            $profile = MentorProfile::factory()->create(['user_id' => $user->id]);
            MentorReview::factory()->create(['mentor_id' => $user->id, 'rating' => 5]);
            MentorReview::factory()->create(['mentor_id' => $user->id, 'rating' => 4]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, '4.5', 'rating');
            $results = $query->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($profile->id);
        });
    });

    describe('boundary conditions for mutation coverage', function (): void {
        it('includes mentor with rating exactly at threshold 4', function (): void {
            $user = User::factory()->create();
            $profile = MentorProfile::factory()->create(['user_id' => $user->id]);
            MentorReview::factory()->create(['mentor_id' => $user->id, 'rating' => 4]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 4, 'rating');
            $results = $query->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($profile->id);
        });

        it('excludes mentor with rating just below threshold', function (): void {
            $user = User::factory()->create();
            $profile = MentorProfile::factory()->create(['user_id' => $user->id]);
            MentorReview::factory()->create(['mentor_id' => $user->id, 'rating' => 4]);
            MentorReview::factory()->create(['mentor_id' => $user->id, 'rating' => 3]);
            MentorReview::factory()->create(['mentor_id' => $user->id, 'rating' => 3]);
            MentorReview::factory()->create(['mentor_id' => $user->id, 'rating' => 3]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 4, 'rating');
            $results = $query->get();

            expect($results)->toHaveCount(0)
                ->and($results->pluck('id')->toArray())->not->toContain($profile->id);
        });

        it('verifies >= operator by testing exact threshold match', function (): void {
            $userExact = User::factory()->create();
            $profileExact = MentorProfile::factory()->create(['user_id' => $userExact->id]);
            MentorReview::factory()->create(['mentor_id' => $userExact->id, 'rating' => 3]);

            $userAbove = User::factory()->create();
            $profileAbove = MentorProfile::factory()->create(['user_id' => $userAbove->id]);
            MentorReview::factory()->create(['mentor_id' => $userAbove->id, 'rating' => 4]);

            $userBelow = User::factory()->create();
            $profileBelow = MentorProfile::factory()->create(['user_id' => $userBelow->id]);
            MentorReview::factory()->create(['mentor_id' => $userBelow->id, 'rating' => 2]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 3, 'rating');
            $results = $query->get();

            expect($results)->toHaveCount(2)
                ->and($results->pluck('id')->toArray())->toContain($profileExact->id)
                ->and($results->pluck('id')->toArray())->toContain($profileAbove->id)
                ->and($results->pluck('id')->toArray())->not->toContain($profileBelow->id);
        });
    });

    describe('multiple mentors comparison', function (): void {
        it('filters multiple mentors with different ratings correctly', function (): void {
            $user1 = User::factory()->create();
            $profile1 = MentorProfile::factory()->create(['user_id' => $user1->id]);
            MentorReview::factory()->create(['mentor_id' => $user1->id, 'rating' => 5]);

            $user2 = User::factory()->create();
            $profile2 = MentorProfile::factory()->create(['user_id' => $user2->id]);
            MentorReview::factory()->create(['mentor_id' => $user2->id, 'rating' => 4]);

            $user3 = User::factory()->create();
            $profile3 = MentorProfile::factory()->create(['user_id' => $user3->id]);
            MentorReview::factory()->create(['mentor_id' => $user3->id, 'rating' => 3]);

            $user4 = User::factory()->create();
            $profile4 = MentorProfile::factory()->create(['user_id' => $user4->id]);
            MentorReview::factory()->create(['mentor_id' => $user4->id, 'rating' => 2]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 4, 'rating');
            $results = $query->get();

            expect($results)->toHaveCount(2)
                ->and($results->pluck('id')->toArray())->toContain($profile1->id)
                ->and($results->pluck('id')->toArray())->toContain($profile2->id)
                ->and($results->pluck('id')->toArray())->not->toContain($profile3->id)
                ->and($results->pluck('id')->toArray())->not->toContain($profile4->id);
        });

        it('handles mentors with varying review counts', function (): void {
            $userOneReview = User::factory()->create();
            $profileOneReview = MentorProfile::factory()->create(['user_id' => $userOneReview->id]);
            MentorReview::factory()->create(['mentor_id' => $userOneReview->id, 'rating' => 5]);

            $userThreeReviews = User::factory()->create();
            $profileThreeReviews = MentorProfile::factory()->create(['user_id' => $userThreeReviews->id]);
            MentorReview::factory()->create(['mentor_id' => $userThreeReviews->id, 'rating' => 5]);
            MentorReview::factory()->create(['mentor_id' => $userThreeReviews->id, 'rating' => 4]);
            MentorReview::factory()->create(['mentor_id' => $userThreeReviews->id, 'rating' => 5]);

            $userNoReviews = User::factory()->create();
            $profileNoReviews = MentorProfile::factory()->create(['user_id' => $userNoReviews->id]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 4, 'rating');
            $results = $query->get();

            expect($results)->toHaveCount(2)
                ->and($results->pluck('id')->toArray())->toContain($profileOneReview->id)
                ->and($results->pluck('id')->toArray())->toContain($profileThreeReviews->id)
                ->and($results->pluck('id')->toArray())->not->toContain($profileNoReviews->id);
        });

        it('correctly handles mix of high and low average ratings', function (): void {
            $userHighAvg = User::factory()->create();
            $profileHighAvg = MentorProfile::factory()->create(['user_id' => $userHighAvg->id]);
            MentorReview::factory()->create(['mentor_id' => $userHighAvg->id, 'rating' => 5]);
            MentorReview::factory()->create(['mentor_id' => $userHighAvg->id, 'rating' => 5]);
            MentorReview::factory()->create(['mentor_id' => $userHighAvg->id, 'rating' => 5]);

            $userMidAvg = User::factory()->create();
            $profileMidAvg = MentorProfile::factory()->create(['user_id' => $userMidAvg->id]);
            MentorReview::factory()->create(['mentor_id' => $userMidAvg->id, 'rating' => 4]);
            MentorReview::factory()->create(['mentor_id' => $userMidAvg->id, 'rating' => 4]);

            $userLowAvg = User::factory()->create();
            $profileLowAvg = MentorProfile::factory()->create(['user_id' => $userLowAvg->id]);
            MentorReview::factory()->create(['mentor_id' => $userLowAvg->id, 'rating' => 2]);
            MentorReview::factory()->create(['mentor_id' => $userLowAvg->id, 'rating' => 3]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 4, 'rating');
            $results = $query->get();

            expect($results)->toHaveCount(2)
                ->and($results->pluck('id')->toArray())->toContain($profileHighAvg->id)
                ->and($results->pluck('id')->toArray())->toContain($profileMidAvg->id)
                ->and($results->pluck('id')->toArray())->not->toContain($profileLowAvg->id);
        });
    });

    describe('edge cases', function (): void {
        it('defaults null value to 0.0 excluding mentors without reviews', function (): void {
            $user = User::factory()->create();
            $profile = MentorProfile::factory()->create(['user_id' => $user->id]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, null, 'rating');
            $results = $query->get();

            expect($results)->toHaveCount(0);
        });

        it('defaults non-numeric string to 0.0 requiring mentors have reviews', function (): void {
            $user1 = User::factory()->create();
            $profile1 = MentorProfile::factory()->create(['user_id' => $user1->id]);
            MentorReview::factory()->create(['mentor_id' => $user1->id, 'rating' => 1]);

            $user2 = User::factory()->create();
            $profile2 = MentorProfile::factory()->create(['user_id' => $user2->id]);
            MentorReview::factory()->create(['mentor_id' => $user2->id, 'rating' => 5]);

            $userNoReview = User::factory()->create();
            $profileNoReview = MentorProfile::factory()->create(['user_id' => $userNoReview->id]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 'invalid', 'rating');
            $results = $query->get();

            expect($results)->toHaveCount(2)
                ->and($results->pluck('id')->toArray())->toContain($profile1->id)
                ->and($results->pluck('id')->toArray())->toContain($profile2->id)
                ->and($results->pluck('id')->toArray())->not->toContain($profileNoReview->id);
        });

        it('defaults array value to 0.0 requiring valid reviews', function (): void {
            $userWithReview = User::factory()->create();
            $profileWithReview = MentorProfile::factory()->create(['user_id' => $userWithReview->id]);
            MentorReview::factory()->create(['mentor_id' => $userWithReview->id, 'rating' => 1]);

            $userNoReview = User::factory()->create();
            $profileNoReview = MentorProfile::factory()->create(['user_id' => $userNoReview->id]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, [1, 2, 3], 'rating');
            $results = $query->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($profileWithReview->id);
        });

        it('defaults object value to 0.0 requiring valid reviews', function (): void {
            $userWithReview = User::factory()->create();
            $profileWithReview = MentorProfile::factory()->create(['user_id' => $userWithReview->id]);
            MentorReview::factory()->create(['mentor_id' => $userWithReview->id, 'rating' => 1]);

            $userNoReview = User::factory()->create();
            $profileNoReview = MentorProfile::factory()->create(['user_id' => $userNoReview->id]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, new stdClass, 'rating');
            $results = $query->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($profileWithReview->id);
        });

        it('casts numeric string to float correctly for decimal comparison', function (): void {
            $userHigh = User::factory()->create();
            $profileHigh = MentorProfile::factory()->create(['user_id' => $userHigh->id]);
            MentorReview::factory()->create(['mentor_id' => $userHigh->id, 'rating' => 4]);
            MentorReview::factory()->create(['mentor_id' => $userHigh->id, 'rating' => 5]);

            $userLow = User::factory()->create();
            $profileLow = MentorProfile::factory()->create(['user_id' => $userLow->id]);
            MentorReview::factory()->create(['mentor_id' => $userLow->id, 'rating' => 4]);
            MentorReview::factory()->create(['mentor_id' => $userLow->id, 'rating' => 4]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, '4.5', 'rating');
            $results = $query->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($profileHigh->id)
                ->and($results->pluck('id')->toArray())->not->toContain($profileLow->id);
        });

        it('handles threshold of 1 (minimum rating)', function (): void {
            $user = User::factory()->create();
            $profile = MentorProfile::factory()->create(['user_id' => $user->id]);
            MentorReview::factory()->create(['mentor_id' => $user->id, 'rating' => 1]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 1, 'rating');
            $results = $query->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($profile->id);
        });

        it('handles threshold of 5 (maximum rating)', function (): void {
            $user1 = User::factory()->create();
            $profile1 = MentorProfile::factory()->create(['user_id' => $user1->id]);
            MentorReview::factory()->create(['mentor_id' => $user1->id, 'rating' => 5]);

            $user2 = User::factory()->create();
            $profile2 = MentorProfile::factory()->create(['user_id' => $user2->id]);
            MentorReview::factory()->create(['mentor_id' => $user2->id, 'rating' => 4]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 5, 'rating');
            $results = $query->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($profile1->id)
                ->and($results->pluck('id')->toArray())->not->toContain($profile2->id);
        });

        it('handles zero threshold', function (): void {
            $user = User::factory()->create();
            $profile = MentorProfile::factory()->create(['user_id' => $user->id]);
            MentorReview::factory()->create(['mentor_id' => $user->id, 'rating' => 1]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 0, 'rating');
            $results = $query->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($profile->id);
        });

        it('returns empty when all mentors below threshold', function (): void {
            $user1 = User::factory()->create();
            MentorProfile::factory()->create(['user_id' => $user1->id]);
            MentorReview::factory()->create(['mentor_id' => $user1->id, 'rating' => 2]);

            $user2 = User::factory()->create();
            MentorProfile::factory()->create(['user_id' => $user2->id]);
            MentorReview::factory()->create(['mentor_id' => $user2->id, 'rating' => 3]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 5, 'rating');
            $results = $query->get();

            expect($results)->toHaveCount(0);
        });

        it('includes all mentors when all meet threshold', function (): void {
            $user1 = User::factory()->create();
            $profile1 = MentorProfile::factory()->create(['user_id' => $user1->id]);
            MentorReview::factory()->create(['mentor_id' => $user1->id, 'rating' => 5]);

            $user2 = User::factory()->create();
            $profile2 = MentorProfile::factory()->create(['user_id' => $user2->id]);
            MentorReview::factory()->create(['mentor_id' => $user2->id, 'rating' => 4]);

            $user3 = User::factory()->create();
            $profile3 = MentorProfile::factory()->create(['user_id' => $user3->id]);
            MentorReview::factory()->create(['mentor_id' => $user3->id, 'rating' => 5]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 3, 'rating');
            $results = $query->get();

            expect($results)->toHaveCount(3)
                ->and($results->pluck('id')->toArray())->toContain($profile1->id)
                ->and($results->pluck('id')->toArray())->toContain($profile2->id)
                ->and($results->pluck('id')->toArray())->toContain($profile3->id);
        });
    });
});
