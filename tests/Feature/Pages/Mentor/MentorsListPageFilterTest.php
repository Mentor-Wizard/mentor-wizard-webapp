<?php

declare(strict_types=1);

use App\Actions\Pages\Mentor\MentorsListPage;
use App\Enums\TagEnum;
use App\Models\Currency;
use App\Models\MentorProfile;
use App\Models\MentorReview;
use App\Models\MentorTag;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;

mutates(MentorsListPage::class);

describe('MentorsListPage - Filter Options with Many Items', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        // Create 15 stack tags to test collapse functionality (need >10)
        $stacks = ['Laravel', 'React', 'Vue.js', 'Angular', 'Django', 'Flask', 'Express', 'NestJS', 'Spring', 'Rails', 'Phoenix', 'Symfony', 'FastAPI', 'Actix', 'Rocket'];
        foreach ($stacks as $stack) {
            MentorTag::factory()->create(['type' => TagEnum::STACK, 'tag' => $stack]);
        }

        // Create 12 language tags to test collapse functionality (need >10)
        $languages = ['PHP', 'JavaScript', 'Python', 'Java', 'Ruby', 'Go', 'Rust', 'C#', 'C++', 'Swift', 'Kotlin', 'TypeScript'];
        foreach ($languages as $language) {
            MentorTag::factory()->create(['type' => TagEnum::LANGUAGE, 'tag' => $language]);
        }
    });

    it('returns all stack options when more than 10 exist', function (): void {
        $this->get(route('pages.mentors'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('filtersData.stackOptions', 15)
            );
    });

    it('returns all language options when more than 10 exist', function (): void {
        $this->get(route('pages.mentors'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('filtersData.languageOptions', 12)
            );
    });

    it('stack options are sorted alphabetically', function (): void {
        $this->get(route('pages.mentors'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->where('filtersData.stackOptions.0.label', 'Actix')
                ->where('filtersData.stackOptions.1.label', 'Angular')
            );
    });

    it('language options are sorted alphabetically', function (): void {
        $this->get(route('pages.mentors'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->where('filtersData.languageOptions.0.label', 'C#')
                ->where('filtersData.languageOptions.1.label', 'C++')
            );
    });

    it('each stack option has value and label properties', function (): void {
        $this->get(route('pages.mentors'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('filtersData.stackOptions.0', fn (AssertableInertia $option): AssertableInertia => $option
                    ->has('value')
                    ->has('label')
                    ->etc()
                )
            );
    });

    it('each language option has value and label properties', function (): void {
        $this->get(route('pages.mentors'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('filtersData.languageOptions.0', fn (AssertableInertia $option): AssertableInertia => $option
                    ->has('value')
                    ->has('label')
                    ->etc()
                )
            );
    });
});

describe('MentorsListPage - URL Query Parameters Preservation', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        $this->laravelTag = MentorTag::factory()->create(['type' => TagEnum::STACK, 'tag' => 'Laravel']);
        $this->reactTag = MentorTag::factory()->create(['type' => TagEnum::STACK, 'tag' => 'React']);
    });

    it('preserves stacks filter in query params', function (): void {
        $this->get(route('pages.mentors', ['filter' => ['stacks' => 'Laravel']]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->where('queryParams.filter.stacks', 'Laravel')
            );
    });

    it('preserves multiple stacks in query params', function (): void {
        $this->get(route('pages.mentors', ['filter' => ['stacks' => 'Laravel,React']]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->where('queryParams.filter.stacks', 'Laravel,React')
            );
    });

    it('preserves all filter types in query params', function (): void {
        $this->get(route('pages.mentors', [
            'filter' => [
                'stacks'     => 'Laravel',
                'experience' => 'senior',
                'rate'       => ['min' => 50, 'max' => 100],
                'rating'     => 4,
            ],
        ]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->where('queryParams.filter.stacks', 'Laravel')
                ->where('queryParams.filter.experience', 'senior')
                ->where('queryParams.filter.rate.min', '50')
                ->where('queryParams.filter.rate.max', '100')
                ->where('queryParams.filter.rating', '4')
            );
    });

    it('returns empty queryParams when no filters applied', function (): void {
        $this->get(route('pages.mentors'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('queryParams')
                ->where('queryParams', [])
            );
    });
});

describe('MentorsListPage - Inertia Partial Reloads', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        MentorProfile::factory()->count(3)->create();
    });

    it('supports partial reload for mentors only', function (): void {
        $response = $this->get(route('pages.mentors'))
            ->assertOk();

        $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('Mentor/MentorsListPage')
            ->has('mentors.data', 3)
            ->has('filtersData')
            ->reloadOnly('mentors', fn (AssertableInertia $reload): AssertableInertia => $reload
                ->has('mentors.data', 3)
                ->missing('filtersData')
            )
        );
    });

    it('partial reload returns updated mentor count after filtering', function (): void {
        $laravelTag = MentorTag::factory()->create(['type' => TagEnum::STACK, 'tag' => 'Laravel']);
        $mentor = MentorProfile::factory()->create();
        $mentor->mentorTags()->attach($laravelTag);

        $response = $this->get(route('pages.mentors', ['filter' => ['stacks' => 'Laravel']]))
            ->assertOk();

        $response->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->has('mentors.data', 1)
            ->reloadOnly('mentors', fn (AssertableInertia $reload): AssertableInertia => $reload
                ->has('mentors.data', 1)
                ->missing('filtersData')
            )
        );
    });
});

describe('MentorsListPage - Dynamic Filtering User Flow', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        // Create diverse test data
        $this->laravelTag = MentorTag::factory()->create(['type' => TagEnum::STACK, 'tag' => 'Laravel']);
        $this->reactTag = MentorTag::factory()->create(['type' => TagEnum::STACK, 'tag' => 'React']);
        $this->phpTag = MentorTag::factory()->create(['type' => TagEnum::LANGUAGE, 'tag' => 'PHP']);

        // Laravel + PHP senior mentor
        $this->seniorLaravelMentor = MentorProfile::factory()->create([
            'title'                 => 'Senior Laravel Dev',
            'rate'                  => 100.0,
            'experience_started_at' => now()->subYears(10),
        ]);
        $this->seniorLaravelMentor->mentorTags()->attach([$this->laravelTag->getKey(), $this->phpTag->getKey()]);
        MentorReview::factory()->create(['mentor_id' => $this->seniorLaravelMentor->user_id, 'rating' => 5]);

        // React junior mentor
        $this->juniorReactMentor = MentorProfile::factory()->create([
            'title'                 => 'Junior React Dev',
            'rate'                  => 50.0,
            'experience_started_at' => now()->subYears(2),
        ]);
        $this->juniorReactMentor->mentorTags()->attach($this->reactTag);

        // Laravel + PHP mid-level mentor (lower rate, lower rating)
        $this->midLaravelMentor = MentorProfile::factory()->create([
            'title'                 => 'Mid Laravel Dev',
            'rate'                  => 60.0,
            'experience_started_at' => now()->subYears(5),
        ]);
        $this->midLaravelMentor->mentorTags()->attach([$this->laravelTag->getKey(), $this->phpTag->getKey()]);
        MentorReview::factory()->create(['mentor_id' => $this->midLaravelMentor->user_id, 'rating' => 3]);
    });

    it('simulates user selecting Laravel stack filter', function (): void {
        // User clicks Laravel checkbox
        $this->get(route('pages.mentors', ['filter' => ['stacks' => 'Laravel']]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('mentors.data', 2)
                ->where('queryParams.filter.stacks', 'Laravel')
            );
    });

    it('simulates user adding experience filter to existing stack filter', function (): void {
        // User already filtered by Laravel, now adds senior experience
        $this->get(route('pages.mentors', [
            'filter' => [
                'stacks'     => 'Laravel',
                'experience' => 'senior',
            ],
        ]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('mentors.data', 1)
                ->where('mentors.data.0.title', 'Senior Laravel Dev')
                ->where('queryParams.filter.stacks', 'Laravel')
                ->where('queryParams.filter.experience', 'senior')
            );
    });

    it('simulates user adding minimum rating filter', function (): void {
        // User filters Laravel + senior + min rating 4
        $this->get(route('pages.mentors', [
            'filter' => [
                'stacks'     => 'Laravel',
                'experience' => 'senior',
                'rating'     => 4,
            ],
        ]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('mentors.data', 1)
                ->where('mentors.data.0.title', 'Senior Laravel Dev')
            );
    });

    it('simulates user filtering to zero results', function (): void {
        // User filters for expensive React developers (none exist)
        $this->get(route('pages.mentors', [
            'filter' => [
                'stacks' => 'React',
                'rate'   => ['min' => 150],
            ],
        ]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('mentors.data', 0)
            );
    });

    it('simulates user clearing all filters', function (): void {
        // User navigates back to unfiltered page
        $this->get(route('pages.mentors'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('mentors.data', 3)
                ->where('queryParams', [])
            );
    });

    it('simulates user combining stack and language filters', function (): void {
        // User filters Laravel stack + PHP language
        $this->get(route('pages.mentors', [
            'filter' => [
                'stacks'    => 'Laravel',
                'languages' => 'PHP',
            ],
        ]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('mentors.data', 2)
            );
    });

    it('simulates user progressively narrowing search', function (): void {
        // Step 1: Filter by Laravel
        $step1 = $this->get(route('pages.mentors', ['filter' => ['stacks' => 'Laravel']]));
        $step1->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->has('mentors.data', 2)
        );

        // Step 2: Add rate filter
        $step2 = $this->get(route('pages.mentors', [
            'filter' => [
                'stacks' => 'Laravel',
                'rate'   => ['min' => 80],
            ],
        ]));
        $step2->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->has('mentors.data', 1)
            ->where('mentors.data.0.title', 'Senior Laravel Dev')
        );

        // Step 3: Add minimum rating
        $step3 = $this->get(route('pages.mentors', [
            'filter' => [
                'stacks' => 'Laravel',
                'rate'   => ['min' => 80],
                'rating' => 4,
            ],
        ]));
        $step3->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->has('mentors.data', 1)
            ->where('mentors.data.0.title', 'Senior Laravel Dev')
        );
    });

    it('simulates removing filters one by one', function (): void {
        // Start with all filters
        $step1 = $this->get(route('pages.mentors', [
            'filter' => [
                'stacks'     => 'Laravel',
                'experience' => 'senior',
                'rating'     => 4,
            ],
        ]));
        $step1->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->has('mentors.data', 1)
        );

        // Remove rating filter
        $step2 = $this->get(route('pages.mentors', [
            'filter' => [
                'stacks'     => 'Laravel',
                'experience' => 'senior',
            ],
        ]));
        $step2->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->has('mentors.data', 1) // Still only senior Laravel devs
        );

        // Remove experience filter
        $step3 = $this->get(route('pages.mentors', [
            'filter' => [
                'stacks' => 'Laravel',
            ],
        ]));
        $step3->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->has('mentors.data', 2) // Now includes mid-level Laravel dev too
        );
    });
});

describe('MentorsListPage - Edge Cases', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    it('handles non-existent stack tag gracefully', function (): void {
        MentorProfile::factory()->count(3)->create();

        $this->get(route('pages.mentors', ['filter' => ['stacks' => 'NonExistentStack']]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('mentors.data', 0)
                ->where('queryParams.filter.stacks', 'NonExistentStack')
            );
    });

    it('handles non-existent language tag gracefully', function (): void {
        MentorProfile::factory()->count(3)->create();

        $this->get(route('pages.mentors', ['filter' => ['languages' => 'NonExistentLanguage']]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('mentors.data', 0)
                ->where('queryParams.filter.languages', 'NonExistentLanguage')
            );
    });

    it('filters are case-sensitive for exact tag matching', function (): void {
        $laravelTag = MentorTag::factory()->create(['type' => TagEnum::STACK, 'tag' => 'Laravel']);
        $mentor = MentorProfile::factory()->create();
        $mentor->mentorTags()->attach($laravelTag);

        // Lowercase 'laravel' should not match 'Laravel' tag
        $this->get(route('pages.mentors', ['filter' => ['stacks' => 'laravel']]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('mentors.data', 0)
            );

        // Exact case match should work
        $this->get(route('pages.mentors', ['filter' => ['stacks' => 'Laravel']]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('mentors.data', 1)
            );
    });

    it('handles empty filter array', function (): void {
        MentorProfile::factory()->count(3)->create();

        $this->get(route('pages.mentors', ['filter' => []]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('mentors.data', 3)
            );
    });

    it('handles malformed rate filter (missing min)', function (): void {
        MentorProfile::factory()->create(['rate' => 50.0]);
        MentorProfile::factory()->create(['rate' => 150.0]);

        $this->get(route('pages.mentors', ['filter' => ['rate' => ['max' => 100]]]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('mentors.data', 1)
            );
    });

    it('handles malformed rate filter (missing max)', function (): void {
        MentorProfile::factory()->create(['rate' => 50.0]);
        MentorProfile::factory()->create(['rate' => 150.0]);

        $this->get(route('pages.mentors', ['filter' => ['rate' => ['min' => 100]]]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('mentors.data', 1)
            );
    });
});

describe('MentorsListPage - Sorting', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        $this->cheapMentor = MentorProfile::factory()->create([
            'title'                 => 'Cheap Mentor',
            'rate'                  => 25.0,
            'experience_started_at' => now()->subYears(2),
        ]);

        $this->midMentor = MentorProfile::factory()->create([
            'title'                 => 'Mid Mentor',
            'rate'                  => 75.0,
            'experience_started_at' => now()->subYears(6),
        ]);

        $this->expensiveMentor = MentorProfile::factory()->create([
            'title'                 => 'Expensive Mentor',
            'rate'                  => 150.0,
            'experience_started_at' => now()->subYears(12),
        ]);
    });

    it('sorts mentors by rate ascending', function (): void {
        $this->get(route('pages.mentors', ['sort' => 'rate']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('mentors.data', 3)
                ->where('mentors.data.0.title', 'Cheap Mentor')
                ->where('mentors.data.1.title', 'Mid Mentor')
                ->where('mentors.data.2.title', 'Expensive Mentor')
            );
    });

    it('sorts mentors by rate descending', function (): void {
        $this->get(route('pages.mentors', ['sort' => '-rate']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('mentors.data', 3)
                ->where('mentors.data.0.title', 'Expensive Mentor')
                ->where('mentors.data.1.title', 'Mid Mentor')
                ->where('mentors.data.2.title', 'Cheap Mentor')
            );
    });

    it('sorts mentors by newest (id descending)', function (): void {
        $this->get(route('pages.mentors', ['sort' => '-id']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('mentors.data', 3)
                ->where('mentors.data.0.title', 'Expensive Mentor')
                ->where('mentors.data.1.title', 'Mid Mentor')
                ->where('mentors.data.2.title', 'Cheap Mentor')
            );
    });

    it('sorts mentors by most experienced', function (): void {
        $this->get(route('pages.mentors', ['sort' => 'experience_started_at']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('mentors.data', 3)
                ->where('mentors.data.0.title', 'Expensive Mentor')
                ->where('mentors.data.1.title', 'Mid Mentor')
                ->where('mentors.data.2.title', 'Cheap Mentor')
            );
    });

    it('preserves sort with filters', function (): void {
        $laravelTag = MentorTag::factory()->create(['type' => TagEnum::STACK, 'tag' => 'Laravel']);
        $this->cheapMentor->mentorTags()->attach($laravelTag);
        $this->expensiveMentor->mentorTags()->attach($laravelTag);

        $this->get(route('pages.mentors', [
            'filter' => ['stacks' => 'Laravel'],
            'sort'   => '-rate',
        ]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('mentors.data', 2)
                ->where('mentors.data.0.title', 'Expensive Mentor')
                ->where('mentors.data.1.title', 'Cheap Mentor')
            );
    });

    it('preserves sort in query params', function (): void {
        $this->get(route('pages.mentors', ['sort' => '-rate']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->where('queryParams.sort', '-rate')
            );
    });

    it('sorts by rate normalized to USD across different currencies', function (): void {
        MentorProfile::query()->delete();

        $usd = Currency::factory()->create(['name' => 'USD', 'symbol' => '$', 'exchange_rate' => 1.0]);
        $uah = Currency::factory()->create(['name' => 'UAH', 'symbol' => '₴', 'exchange_rate' => 0.024]);
        $eur = Currency::factory()->create(['name' => 'EUR', 'symbol' => '€', 'exchange_rate' => 1.08]);

        MentorProfile::factory()->create([
            'title'       => 'UAH Mentor',
            'rate'        => 1000.0,
            'currency_id' => $uah->getKey(),
        ]);

        MentorProfile::factory()->create([
            'title'       => 'EUR Mentor',
            'rate'        => 50.0,
            'currency_id' => $eur->getKey(),
        ]);

        MentorProfile::factory()->create([
            'title'       => 'USD Mentor',
            'rate'        => 30.0,
            'currency_id' => $usd->getKey(),
        ]);

        $this->get(route('pages.mentors', ['sort' => 'rate']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('mentors.data', 3)
                ->where('mentors.data.0.title', 'UAH Mentor')
                ->where('mentors.data.1.title', 'USD Mentor')
                ->where('mentors.data.2.title', 'EUR Mentor')
            );
    });
});

describe('MentorsListPage - Data Integrity & Edge Cases', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    it('handles mentor without currency gracefully', function (): void {
        $mentor = MentorProfile::factory()->create();

        DB::statement('ALTER TABLE mentor_profiles ALTER COLUMN currency_id DROP NOT NULL');
        DB::table('mentor_profiles')
            ->where('id', $mentor->getKey())
            ->update(['currency_id' => null]);

        $this->get(route('pages.mentors'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('mentors.data', 1)
                ->where('mentors.data.0.currency.code', 'USD')
                ->where('mentors.data.0.currency.symbol', '$')
            );
    });

    it('returns unique tag options without duplicates', function (): void {
        MentorTag::factory()->count(3)->create(['type' => TagEnum::STACK, 'tag' => 'Laravel']);

        $this->get(route('pages.mentors'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('filtersData.stackOptions', 1)
                ->where('filtersData.stackOptions.0.label', 'Laravel')
            );
    });

    it('returns slug in mentor data for profile links', function (): void {
        $mentor = MentorProfile::factory()->create();
        $user = $mentor->user;

        $this->get(route('pages.mentors'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('mentors.data', 1)
                ->has('mentors.data.0.slug')
                ->where('mentors.data.0.slug', $user->slug)
            );
    });

    it('returns rating from eager loaded avg without N+1', function (): void {
        $mentor = MentorProfile::factory()->create();

        MentorReview::factory()->create(['mentor_id' => $mentor->user_id, 'rating' => 5]);
        MentorReview::factory()->create(['mentor_id' => $mentor->user_id, 'rating' => 4]);
        MentorReview::factory()->create(['mentor_id' => $mentor->user_id, 'rating' => 3]);

        DB::enableQueryLog();

        $this->get(route('pages.mentors'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('mentors.data', 1)
                ->where('mentors.data.0.rating', fn ($rating): bool => (float) $rating === 4.0)
            );

        $queryCount = count(DB::getQueryLog());
        expect($queryCount)->toBeLessThan(15);

        DB::disableQueryLog();
    });

    it('handles page beyond last page gracefully', function (): void {
        MentorProfile::factory()->count(3)->create();

        $this->get(route('pages.mentors', ['page' => 999]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('mentors.data', 0)
            );
    });

    it('paginates at 6 items per page', function (): void {
        MentorProfile::factory()->count(8)->create();

        $this->get(route('pages.mentors'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('mentors.data', 6)
            );

        $this->get(route('pages.mentors', ['page' => 2]))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('mentors.data', 2)
            );
    });
});
