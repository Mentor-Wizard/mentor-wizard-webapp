<?php

declare(strict_types=1);

use App\Actions\Pages\Mentor\MentorsListPage;
use App\Enums\TagEnum;
use App\Models\MentorProfile;
use App\Models\MentorReview;
use App\Models\MentorTag;
use Database\Seeders\RoleSeeder;
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
