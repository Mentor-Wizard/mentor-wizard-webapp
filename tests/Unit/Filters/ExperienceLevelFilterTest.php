<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Filters\ExperienceLevelFilter;
use App\Models\MentorProfile;
use Spatie\Permission\Models\Role;
use Spatie\QueryBuilder\Filters\Filter;

mutates(ExperienceLevelFilter::class);

describe('ExperienceLevelFilter', function (): void {
    beforeEach(function (): void {
        // Create required role for UserObserver
        Role::create(['name' => RoleEnum::USER->value]);

        $this->filter = new ExperienceLevelFilter;
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
        $reflection = new ReflectionClass(ExperienceLevelFilter::class);
        $constructor = $reflection->getConstructor();

        if ($constructor !== null) {
            expect($constructor->getNumberOfRequiredParameters())->toBe(0);
        }

        expect(new ExperienceLevelFilter)->toBeInstanceOf(ExperienceLevelFilter::class);
    });

    describe('entry level filtering (1-3 years)', function (): void {
        it('filters mentors with exactly 1 year experience', function (): void {
            $entryMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYear(),
            ]);

            $midMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(5),
            ]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 'entry', 'experience');
            $results = $query->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($entryMentor->id)
                ->and($results->pluck('id')->toArray())->toContain($entryMentor->id)
                ->and($results->pluck('id')->toArray())->not->toContain($midMentor->id);
        });

        it('filters mentors with exactly 2 years experience', function (): void {
            $entryMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(2),
            ]);

            $seniorMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(10),
            ]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 'entry', 'experience');
            $results = $query->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($entryMentor->id)
                ->and($results->pluck('id')->toArray())->not->toContain($seniorMentor->id);
        });

        it('filters mentors at entry level upper boundary (3 years)', function (): void {
            $entryMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(3),
            ]);

            $expertMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(15),
            ]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 'entry', 'experience');
            $results = $query->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($entryMentor->id)
                ->and($results->pluck('id')->toArray())->not->toContain($expertMentor->id);
        });

        it('excludes mentors with less than 1 year experience', function (): void {
            $tooNewMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subMonths(6),
            ]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 'entry', 'experience');
            $results = $query->get();

            expect($results)->toBeEmpty()
                ->and($results->pluck('id')->toArray())->not->toContain($tooNewMentor->id);
        });

        it('excludes mentors with more than 3 years experience', function (): void {
            $midMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(4),
            ]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 'entry', 'experience');
            $results = $query->get();

            expect($results)->toBeEmpty()
                ->and($results->pluck('id')->toArray())->not->toContain($midMentor->id);
        });

        it('only returns mentors within the specified entry level range', function (): void {
            $entryMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(2),
            ]);

            $outsideEntry = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(10),
            ]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 'entry', 'experience');
            $results = $query->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($entryMentor->id)
                ->and($results->pluck('id')->toArray())->not->toContain($outsideEntry->id);
        });
    });

    describe('mid level filtering (4-7 years)', function (): void {
        it('filters mentors with exactly 4 years experience', function (): void {
            $midMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(4),
            ]);

            $entryMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(2),
            ]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 'mid', 'experience');
            $results = $query->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($midMentor->id)
                ->and($results->pluck('id')->toArray())->not->toContain($entryMentor->id);
        });

        it('filters mentors with exactly 5 years experience', function (): void {
            $midMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(5),
            ]);

            $seniorMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(9),
            ]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 'mid', 'experience');
            $results = $query->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($midMentor->id)
                ->and($results->pluck('id')->toArray())->not->toContain($seniorMentor->id);
        });

        it('filters mentors at mid level upper boundary (7 years)', function (): void {
            $midMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(7),
            ]);

            $seniorMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(8),
            ]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 'mid', 'experience');
            $results = $query->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($midMentor->id)
                ->and($results->pluck('id')->toArray())->not->toContain($seniorMentor->id);
        });

        it('excludes mentors with less than 4 years experience', function (): void {
            $entryMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(3),
            ]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 'mid', 'experience');
            $results = $query->get();

            expect($results)->toBeEmpty()
                ->and($results->pluck('id')->toArray())->not->toContain($entryMentor->id);
        });

        it('excludes mentors with more than 7 years experience', function (): void {
            $seniorMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(8),
            ]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 'mid', 'experience');
            $results = $query->get();

            expect($results)->toBeEmpty()
                ->and($results->pluck('id')->toArray())->not->toContain($seniorMentor->id);
        });
    });

    describe('senior level filtering (8-12 years)', function (): void {
        it('filters mentors with exactly 8 years experience', function (): void {
            $seniorMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(8),
            ]);

            $midMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(6),
            ]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 'senior', 'experience');
            $results = $query->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($seniorMentor->id)
                ->and($results->pluck('id')->toArray())->not->toContain($midMentor->id);
        });

        it('filters mentors with exactly 10 years experience', function (): void {
            $seniorMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(10),
            ]);

            $expertMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(15),
            ]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 'senior', 'experience');
            $results = $query->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($seniorMentor->id)
                ->and($results->pluck('id')->toArray())->not->toContain($expertMentor->id);
        });

        it('filters mentors at senior level upper boundary (12 years)', function (): void {
            $seniorMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(12),
            ]);

            $expertMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(13),
            ]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 'senior', 'experience');
            $results = $query->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($seniorMentor->id)
                ->and($results->pluck('id')->toArray())->not->toContain($expertMentor->id);
        });

        it('excludes mentors with less than 8 years experience', function (): void {
            $midMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(7),
            ]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 'senior', 'experience');
            $results = $query->get();

            expect($results)->toBeEmpty()
                ->and($results->pluck('id')->toArray())->not->toContain($midMentor->id);
        });

        it('excludes mentors with more than 12 years experience', function (): void {
            $expertMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(13),
            ]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 'senior', 'experience');
            $results = $query->get();

            expect($results)->toBeEmpty()
                ->and($results->pluck('id')->toArray())->not->toContain($expertMentor->id);
        });
    });

    describe('expert level filtering (12+ years)', function (): void {
        it('filters mentors with exactly 12 years experience', function (): void {
            $expertMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(12),
            ]);

            $seniorMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(11),
            ]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 'expert', 'experience');
            $results = $query->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($expertMentor->id)
                ->and($results->pluck('id')->toArray())->not->toContain($seniorMentor->id);
        });

        it('filters mentors with exactly 15 years experience', function (): void {
            $expertMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(15),
            ]);

            $seniorMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(10),
            ]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 'expert', 'experience');
            $results = $query->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($expertMentor->id)
                ->and($results->pluck('id')->toArray())->not->toContain($seniorMentor->id);
        });

        it('filters mentors with 20 years experience', function (): void {
            $expertMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(20),
            ]);

            $midMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(5),
            ]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 'expert', 'experience');
            $results = $query->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($expertMentor->id)
                ->and($results->pluck('id')->toArray())->not->toContain($midMentor->id);
        });

        it('excludes mentors with less than 12 years experience', function (): void {
            $seniorMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(11),
            ]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 'expert', 'experience');
            $results = $query->get();

            expect($results)->toBeEmpty()
                ->and($results->pluck('id')->toArray())->not->toContain($seniorMentor->id);
        });

        it('includes all mentors with 12 or more years experience', function (): void {
            $expert12 = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(12),
            ]);

            $expert13 = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(13),
            ]);

            $expert20 = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(20),
            ]);

            $senior11 = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(11),
            ]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 'expert', 'experience');
            $results = $query->get();

            expect($results)->toHaveCount(3)
                ->and($results->pluck('id')->toArray())->toContain($expert12->id)
                ->and($results->pluck('id')->toArray())->toContain($expert13->id)
                ->and($results->pluck('id')->toArray())->toContain($expert20->id)
                ->and($results->pluck('id')->toArray())->not->toContain($senior11->id);
        });
    });

    describe('multiple level filtering', function (): void {
        it('filters by entry and mid levels with comma-separated string', function (): void {
            $entryMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(2),
            ]);

            $midMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(5),
            ]);

            $seniorMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(10),
            ]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 'entry,mid', 'experience');
            $results = $query->get();

            expect($results)->toHaveCount(2)
                ->and($results->pluck('id')->toArray())->toContain($entryMentor->id)
                ->and($results->pluck('id')->toArray())->toContain($midMentor->id)
                ->and($results->pluck('id')->toArray())->not->toContain($seniorMentor->id);
        });

        it('filters by mid and senior levels with array input', function (): void {
            $midMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(6),
            ]);

            $seniorMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(9),
            ]);

            $expertMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(15),
            ]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, ['mid', 'senior'], 'experience');
            $results = $query->get();

            expect($results)->toHaveCount(2)
                ->and($results->pluck('id')->toArray())->toContain($midMentor->id)
                ->and($results->pluck('id')->toArray())->toContain($seniorMentor->id)
                ->and($results->pluck('id')->toArray())->not->toContain($expertMentor->id);
        });

        it('filters by all four experience levels', function (): void {
            $entryMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(2),
            ]);

            $midMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(5),
            ]);

            $seniorMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(10),
            ]);

            $expertMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(15),
            ]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 'entry,mid,senior,expert', 'experience');
            $results = $query->get();

            expect($results)->toHaveCount(4)
                ->and($results->pluck('id')->toArray())->toContain($entryMentor->id)
                ->and($results->pluck('id')->toArray())->toContain($midMentor->id)
                ->and($results->pluck('id')->toArray())->toContain($seniorMentor->id)
                ->and($results->pluck('id')->toArray())->toContain($expertMentor->id);
        });

        it('filters by senior and expert levels with spaces in string', function (): void {
            $seniorMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(11),
            ]);

            $expertMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(14),
            ]);

            $entryMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(2),
            ]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 'senior, expert', 'experience');
            $results = $query->get();

            expect($results)->toHaveCount(2)
                ->and($results->pluck('id')->toArray())->toContain($seniorMentor->id)
                ->and($results->pluck('id')->toArray())->toContain($expertMentor->id)
                ->and($results->pluck('id')->toArray())->not->toContain($entryMentor->id);
        });
    });

    describe('edge cases and invalid inputs', function (): void {
        it('handles mixed valid and invalid level names', function (): void {
            $midMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(5),
            ]);

            $entryMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(2),
            ]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 'mid,invalid,unknown', 'experience');
            $results = $query->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($midMentor->id)
                ->and($results->pluck('id')->toArray())->not->toContain($entryMentor->id);
        });

        it('casts integer value to string before exploding', function (): void {
            $entryMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(2),
            ]);

            $midMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(5),
            ]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 123, 'experience');
            $results = $query->get();

            expect($results)->toHaveCount(2);
        });

        it('casts object with __toString to string before exploding', function (): void {
            $stringableObject = new class
            {
                public function __toString(): string
                {
                    return 'entry';
                }
            };

            $entryMentor = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(2),
            ]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, $stringableObject, 'experience');
            $results = $query->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($entryMentor->id);
        });
    });

    describe('boundary testing for mutation coverage', function (): void {
        it('verifies exact boundary at 1 year is included in entry', function (): void {
            $exactlyOneYear = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYear(),
            ]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 'entry', 'experience');
            $results = $query->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($exactlyOneYear->id);
        });

        it('verifies exact boundary at 3 years is included in entry', function (): void {
            $exactlyThreeYears = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(3),
            ]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 'entry', 'experience');
            $results = $query->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($exactlyThreeYears->id);
        });

        it('verifies exact boundary at 4 years is included in mid', function (): void {
            $exactlyFourYears = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(4),
            ]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 'mid', 'experience');
            $results = $query->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($exactlyFourYears->id);
        });

        it('verifies exact boundary at 7 years is included in mid', function (): void {
            $exactlySevenYears = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(7),
            ]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 'mid', 'experience');
            $results = $query->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($exactlySevenYears->id);
        });

        it('verifies exact boundary at 8 years is included in senior', function (): void {
            $exactlyEightYears = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(8),
            ]);

            $query = MentorProfile::query();
            $this->filter->__invoke($query, 'senior', 'experience');
            $results = $query->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->id)->toBe($exactlyEightYears->id);
        });

        it('verifies exact boundary at 12 years is included in both senior and expert', function (): void {
            $exactlyTwelveYears = MentorProfile::factory()->create([
                'experience_started_at' => now()->subYears(12),
            ]);

            $querySenior = MentorProfile::query();
            $this->filter->__invoke($querySenior, 'senior', 'experience');
            $seniorResults = $querySenior->get();

            $queryExpert = MentorProfile::query();
            $this->filter->__invoke($queryExpert, 'expert', 'experience');
            $expertResults = $queryExpert->get();

            expect($seniorResults)->toHaveCount(1)
                ->and($seniorResults->first()->id)->toBe($exactlyTwelveYears->id)
                ->and($expertResults)->toHaveCount(1)
                ->and($expertResults->first()->id)->toBe($exactlyTwelveYears->id);
        });
    });
});
