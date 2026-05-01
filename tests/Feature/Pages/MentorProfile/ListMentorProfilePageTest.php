<?php

declare(strict_types=1);

use App\Actions\Pages\Profile\ListMentorProfilePage;
use App\Console\Commands\CacheCategoryTree;
use App\Enums\RoleEnum;
use App\Enums\TagEnum;
use App\Models\Category;
use App\Models\MentorProfile;
use App\Models\MentorProgram;
use App\Models\MentorReview;
use App\Models\MentorTag;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;

mutates(ListMentorProfilePage::class);

describe('ListMentorProfilePage filters and includes', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $mentor = User::factory()->create();
        $mentor->assignRole(RoleEnum::MENTOR->value);

        // Create profiles
        $this->profileA = MentorProfile::factory()->create(['title' => 'Laravel Guru', 'description' => 'Senior dev', 'rate' => 80.0]);
        $this->profileB = MentorProfile::factory()->create(['title' => 'React Ninja', 'description' => 'Frontend pro', 'rate' => 75.0]);
        $this->profileC = MentorProfile::factory()->create(['title' => 'Python Master', 'description' => 'Monty Guy', 'rate' => 40.0]);

        // Programs
        $this->programA1 = MentorProgram::factory()->create(['name' => 'Advanced PHP', 'description' => 'PHP', 'cost' => 600, 'mentor_id' => $mentor->getKey()]);
        $this->programB1 = MentorProgram::factory()->create(['name' => 'React Basics', 'description' => 'React', 'cost' => 200, 'mentor_id' => $mentor->getKey()]);
        $this->programC1 = MentorProgram::factory()->create(['name' => 'Python Junior', 'description' => 'Python', 'cost' => 50, 'mentor_id' => $mentor->getKey()]);

        // Attach programs to profiles
        $this->profileA->mentorPrograms()->attach($this->programA1->getKey());
        $this->profileB->mentorPrograms()->attach($this->programB1->getKey());
        $this->profileC->mentorPrograms()->attach($this->programC1->getKey());

        // Tags
        $this->tagPhp = MentorTag::factory()->create(['type' => TagEnum::LANGUAGE, 'tag' => 'PHP']);
        $this->tagJs = MentorTag::factory()->create(['type' => TagEnum::LANGUAGE, 'tag' => 'JavaScript']);
        $this->tagPy = MentorTag::factory()->create(['type' => TagEnum::LANGUAGE, 'tag' => 'Python']);
        $this->tagLar = MentorTag::factory()->create(['type' => TagEnum::STACK, 'tag' => 'Laravel']);
        $this->tagVue = MentorTag::factory()->create(['type' => TagEnum::STACK, 'tag' => 'Vue.js']);
        $this->tagReact = MentorTag::factory()->create(['type' => TagEnum::STACK, 'tag' => 'Django']);

        // Attach tags
        $this->profileA->mentorTags()->attach([$this->tagPhp->getKey(), $this->tagJs->getKey(), $this->tagLar->getKey(), $this->tagVue->getKey()]);
        $this->profileB->mentorTags()->attach([$this->tagPy->getKey(), $this->tagReact->getKey()]);
        $this->profileC->mentorTags()->attach([$this->tagPhp->getKey(), $this->tagLar->getKey()]);

        // Normalize experience_started_at for sorting assertions
        $this->profileA->update(['experience_started_at' => '2018-01-01']);
        $this->profileB->update(['experience_started_at' => '2020-01-01']);
        $this->profileC->update(['experience_started_at' => '2010-01-01']);
    });

    it('lists all profiles by default (no filters)', function (): void {
        $response = $this->get(route('page.profile-programs'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page): Assert => $page
            ->component('Profile/MentorListPage')
            ->has('mentors.data', 3)
        );
    });

    it('filters by title', function (): void {
        $response = $this->get(route('page.profile-programs', ['filter' => ['title' => 'Laravel']]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page): Assert => $page
            ->component('Profile/MentorListPage')
            ->has('mentors.data', 1)
            ->where('mentors.data.0.title', 'Laravel Guru')
        );
    });

    it('filters by description', function (): void {
        $response = $this->get(route('page.profile-programs', ['filter' => ['description' => 'Frontend']]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page): Assert => $page
            ->component('Profile/MentorListPage')
            ->has('mentors.data', 1)
            ->where('mentors.data.0.title', 'React Ninja')
        );
    });

    it('filters by mentor program name', function (): void {
        $response = $this->get(route('page.profile-programs', ['filter' => ['mentorPrograms.name' => 'Advanced']]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page): Assert => $page
            ->component('Profile/MentorListPage')
            ->has('mentors.data', 1)
            ->where('mentors.data.0.title', 'Laravel Guru')
        );
    });

    it('filters by mentor program description', function (): void {
        $response = $this->get(route('page.profile-programs', ['filter' => ['mentorPrograms.description' => 'Python']]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page): Assert => $page
            ->component('Profile/MentorListPage')
            ->has('mentors.data', 1)
            ->where('mentors.data.0.title', 'Python Master')
        );
    });

    it('filters by rate range using array params', function (): void {
        $response = $this->get(route('page.profile-programs', ['filter' => ['rate' => ['min' => 50, 'max' => 90]]]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page): Assert => $page
            ->component('Profile/MentorListPage')
            ->has('mentors.data', 2)
        );

        $response2 = $this->get(route('page.profile-programs', ['filter' => ['rate' => ['min' => 0, 'max' => 50]]]));

        $response2->assertOk();
        $response2->assertInertia(fn (Assert $page): Assert => $page
            ->component('Profile/MentorListPage')
            ->has('mentors.data', 1)
            ->where('mentors.data.0.title', 'Python Master')
        );
    });

    it('filters by cost range via related mentorPrograms using array params', function (): void {
        $response = $this->get(route('page.profile-programs', ['filter' => ['cost' => ['min' => 0, 'max' => 300]]]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page): Assert => $page
            ->component('Profile/MentorListPage')
            ->has('mentors.data', 2)
        );

        $response2 = $this->get(route('page.profile-programs', ['filter' => ['cost' => ['min' => 500, 'max' => 700]]]));

        $response2->assertOk();
        $response2->assertInertia(fn (Assert $page): Assert => $page
            ->component('Profile/MentorListPage')
            ->has('mentors.data', 1)
            ->where('mentors.data.0.title', 'Laravel Guru')
        );
    });

    it('filters by languages and stacks', function (): void {
        $response = $this->get(route('page.profile-programs', ['filter' => ['languages' => 'PHP']]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page): Assert => $page
            ->component('Profile/MentorListPage')
            ->has('mentors.data', 2)
        );

        $response2 = $this->get(route('page.profile-programs', ['filter' => ['stacks' => 'Laravel']]));

        $response2->assertOk();
        $response2->assertInertia(fn (Assert $page): Assert => $page
            ->component('Profile/MentorListPage')
            ->has('mentors.data', 2)
        );
    });

    it('supports sorting by rate desc', function (): void {
        $response = $this->get(route('page.profile-programs', ['sort' => '-rate']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page): Assert => $page
            ->component('Profile/MentorListPage')
            ->where('mentors.data.0.title', 'Laravel Guru')
            ->where('mentors.data.1.title', 'React Ninja')
            ->where('mentors.data.2.title', 'Python Master')
        );
    });

    it('supports sorting by id desc', function (): void {
        $response = $this->get(route('page.profile-programs', ['sort' => '-id']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page): Assert => $page
            ->component('Profile/MentorListPage')
            ->where('mentors.data.0.title', 'Python Master')
            ->where('mentors.data.1.title', 'React Ninja')
            ->where('mentors.data.2.title', 'Laravel Guru')
        );
    });

    it('supports sorting by experience_started_at asc', function (): void {
        $response = $this->get(route('page.profile-programs', ['sort' => 'experience_started_at']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page): Assert => $page
            ->component('Profile/MentorListPage')
            ->where('mentors.data.0.title', 'Python Master')
            ->where('mentors.data.1.title', 'Laravel Guru')
            ->where('mentors.data.2.title', 'React Ninja')
        );
    });

    it('filters by single experience level', function (): void {
        MentorProfile::query()->delete();

        MentorProfile::factory()->create([
            'title'                 => 'Entry Level Dev',
            'experience_started_at' => now()->subYears(2),
        ]);
        MentorProfile::factory()->create([
            'title'                 => 'Mid Level Dev',
            'experience_started_at' => now()->subYears(5),
        ]);
        MentorProfile::factory()->create([
            'title'                 => 'Senior Dev',
            'experience_started_at' => now()->subYears(10),
        ]);
        MentorProfile::factory()->create([
            'title'                 => 'Expert Dev',
            'experience_started_at' => now()->subYears(15),
        ]);

        $this->get(route('page.profile-programs', ['filter' => ['experience' => 'entry']]))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->has('mentors.data', 1)
                ->where('mentors.data.0.title', 'Entry Level Dev')
            );

        $this->get(route('page.profile-programs', ['filter' => ['experience' => 'mid']]))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->has('mentors.data', 1)
                ->where('mentors.data.0.title', 'Mid Level Dev')
            );

        $this->get(route('page.profile-programs', ['filter' => ['experience' => 'senior']]))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->has('mentors.data', 1)
                ->where('mentors.data.0.title', 'Senior Dev')
            );

        $this->get(route('page.profile-programs', ['filter' => ['experience' => 'expert']]))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->has('mentors.data', 1)
                ->where('mentors.data.0.title', 'Expert Dev')
            );
    });

    it('filters by multiple experience levels', function (): void {
        MentorProfile::query()->delete();

        MentorProfile::factory()->create([
            'title'                 => 'Entry Level Dev',
            'experience_started_at' => now()->subYears(2),
        ]);
        MentorProfile::factory()->create([
            'title'                 => 'Mid Level Dev',
            'experience_started_at' => now()->subYears(5),
        ]);
        MentorProfile::factory()->create([
            'title'                 => 'Senior Dev',
            'experience_started_at' => now()->subYears(10),
        ]);
        MentorProfile::factory()->create([
            'title'                 => 'Expert Dev',
            'experience_started_at' => now()->subYears(15),
        ]);

        $this->get(route('page.profile-programs', ['filter' => ['experience' => 'mid,senior']]))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->has('mentors.data', 2)
            );

        $this->get(route('page.profile-programs', ['filter' => ['experience' => 'entry,expert']]))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->has('mentors.data', 2)
            );
    });

    it('filters by minimum rating', function (): void {
        MentorProfile::query()->delete();

        $mentor1 = MentorProfile::factory()->create(['title' => 'Low Rated Mentor']);
        $mentor2 = MentorProfile::factory()->create(['title' => 'Good Rated Mentor']);
        $mentor3 = MentorProfile::factory()->create(['title' => 'Excellent Rated Mentor']);
        MentorProfile::factory()->create(['title' => 'No Rating Mentor']);

        MentorReview::factory()->create(['mentor_id' => $mentor1->user_id, 'rating' => 2]);
        MentorReview::factory()->create(['mentor_id' => $mentor1->user_id, 'rating' => 3]);

        MentorReview::factory()->create(['mentor_id' => $mentor2->user_id, 'rating' => 4]);
        MentorReview::factory()->create(['mentor_id' => $mentor2->user_id, 'rating' => 4]);

        MentorReview::factory()->create(['mentor_id' => $mentor3->user_id, 'rating' => 5]);
        MentorReview::factory()->create(['mentor_id' => $mentor3->user_id, 'rating' => 5]);
        MentorReview::factory()->create(['mentor_id' => $mentor3->user_id, 'rating' => 4]);

        $this->get(route('page.profile-programs', ['filter' => ['rating' => 4]]))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->has('mentors.data', 2)
            );

        $this->get(route('page.profile-programs', ['filter' => ['rating' => 4.5]]))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->has('mentors.data', 1)
                ->where('mentors.data.0.title', 'Excellent Rated Mentor')
            );

        $this->get(route('page.profile-programs', ['filter' => ['rating' => 3]]))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->has('mentors.data', 2)
            );
    });

    it('combines experience and rating filters', function (): void {
        MentorProfile::query()->delete();

        $mentor1 = MentorProfile::factory()->create([
            'title'                 => 'Senior High Rated',
            'experience_started_at' => now()->subYears(10),
        ]);
        $mentor2 = MentorProfile::factory()->create([
            'title'                 => 'Senior Low Rated',
            'experience_started_at' => now()->subYears(9),
        ]);
        $mentor3 = MentorProfile::factory()->create([
            'title'                 => 'Mid High Rated',
            'experience_started_at' => now()->subYears(5),
        ]);

        MentorReview::factory()->create(['mentor_id' => $mentor1->user_id, 'rating' => 5]);
        MentorReview::factory()->create(['mentor_id' => $mentor1->user_id, 'rating' => 5]);

        MentorReview::factory()->create(['mentor_id' => $mentor2->user_id, 'rating' => 2]);
        MentorReview::factory()->create(['mentor_id' => $mentor2->user_id, 'rating' => 3]);

        MentorReview::factory()->create(['mentor_id' => $mentor3->user_id, 'rating' => 5]);
        MentorReview::factory()->create(['mentor_id' => $mentor3->user_id, 'rating' => 4]);

        $this->get(route('page.profile-programs', ['filter' => ['experience' => 'senior', 'rating' => 4]]))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->has('mentors.data', 1)
                ->where('mentors.data.0.title', 'Senior High Rated')
            );

        $this->get(route('page.profile-programs', ['filter' => ['experience' => 'mid,senior', 'rating' => 4.5]]))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->has('mentors.data', 2)
            );
    });

    it('returns correct data shape for each mentor item', function (): void {
        $response = $this->get(route('page.profile-programs'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page): Assert => $page
            ->component('Profile/MentorListPage')
            ->has('mentors.data.0', fn (Assert $item): Assert => $item
                ->hasAll(['title', 'description', 'rate', 'currency', 'userSlug', 'userName', 'userAvatar', 'mainProgramSlug'])
            )
        );
    });

    it('returns mainProgramSlug when mentor has a main program', function (): void {
        MentorProfile::query()->delete();

        $user = User::factory()->create();
        $user->assignRole(RoleEnum::MENTOR->value);

        $profile = MentorProfile::factory()->create([
            'title'   => 'Mentor With Main Program',
            'user_id' => $user->getKey(),
        ]);

        $mainProgram = MentorProgram::factory()->main()->create([
            'mentor_id' => $user->getKey(),
            'slug'      => 'main-program-slug',
        ]);

        $response = $this->get(route('page.profile-programs'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page): Assert => $page
            ->component('Profile/MentorListPage')
            ->has('mentors.data', 1)
            ->where('mentors.data.0.mainProgramSlug', 'main-program-slug')
        );
    });

    it('returns null mainProgramSlug when mentor has no main program', function (): void {
        MentorProfile::query()->delete();

        $user = User::factory()->create();
        $user->assignRole(RoleEnum::MENTOR->value);

        MentorProfile::factory()->create([
            'title'   => 'Mentor Without Main Program',
            'user_id' => $user->getKey(),
        ]);

        // Create a non-main program
        $mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $user->getKey(),
        ]);

        $mentorProgram->update(['is_main' => false]);
        $mentorProgram->save();

        $response = $this->get(route('page.profile-programs'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page): Assert => $page
            ->component('Profile/MentorListPage')
            ->has('mentors.data', 1)
            ->where('mentors.data.0.mainProgramSlug', null)
        );
    });
});

describe('ListMentorProfilePage category filter', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        Cache::forget(CacheCategoryTree::CACHE_KEY);

        $this->categoryBackend = Category::factory()->create(['name' => 'Backend']);
        $this->categoryFrontend = Category::factory()->create(['name' => 'Frontend']);
        $this->categoryPhp = Category::factory()->child($this->categoryBackend)->create(['name' => 'PHP']);

        $this->profileBackend = MentorProfile::factory()->create(['title' => 'Backend Dev']);
        $this->profilePhp = MentorProfile::factory()->create(['title' => 'PHP Dev']);
        $this->profileFrontend = MentorProfile::factory()->create(['title' => 'Frontend Dev']);
        $this->profileUncategorized = MentorProfile::factory()->create(['title' => 'Uncategorized Dev']);

        $this->profileBackend->categories()->attach($this->categoryBackend->getKey());
        $this->profilePhp->categories()->attach($this->categoryPhp->getKey());
        $this->profileFrontend->categories()->attach($this->categoryFrontend->getKey());
    });

    afterEach(function (): void {
        Cache::forget(CacheCategoryTree::CACHE_KEY);
    });

    it('returns all profiles and empty categories prop when no category_id is given', function (): void {
        $this->get(route('page.profile-programs'))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('Profile/MentorListPage')
                ->has('mentors.data', 4)
                ->where('categories', [])
                ->where('selectedCategoryId', null)
            );
    });

    it('filters profiles by direct category assignment', function (): void {
        $this->get(route('page.profile-programs', ['category_id' => $this->categoryFrontend->getKey()]))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->has('mentors.data', 1)
                ->where('mentors.data.0.title', 'Frontend Dev')
                ->where('selectedCategoryId', $this->categoryFrontend->getKey())
            );
    });

    it('filters profiles by parent category and includes all descendants', function (): void {
        // Backend parent has: profileBackend (direct) + profilePhp (via child PHP category)
        $this->get(route('page.profile-programs', ['category_id' => $this->categoryBackend->getKey()]))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->has('mentors.data', 2)
            );

        $titles = collect(
            $this->get(route('page.profile-programs', ['category_id' => $this->categoryBackend->getKey()]))
                ->assertOk()
                ->original->getData()['page']['props']['mentors']['data']
        )->pluck('title')->sort()->values()->toArray();

        expect($titles)->toBe(['Backend Dev', 'PHP Dev']);
    });

    it('returns empty result when selected category has no assigned profiles', function (): void {
        $empty = Category::factory()->create(['name' => 'Empty Category']);

        $this->get(route('page.profile-programs', ['category_id' => $empty->getKey()]))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->has('mentors.data', 0)
            );
    });

    it('passes the categories tree in props when a category filter is active', function (): void {
        $this->get(route('page.profile-programs', ['category_id' => $this->categoryPhp->getKey()]))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->has('categories')
                ->where('selectedCategoryId', $this->categoryPhp->getKey())
            );
    });

    it('returns 422 for a non-existent category_id', function (): void {
        $this->get(route('page.profile-programs', ['category_id' => 999999]))
            ->assertUnprocessable();
    });

    it('returns 422 for a non-integer category_id', function (): void {
        $this->get(route('page.profile-programs', ['category_id' => 'not-an-integer']))
            ->assertUnprocessable();
    });

    it('withCategories factory state attaches the correct number of categories', function (): void {
        $profile = MentorProfile::factory()->withCategories(3)->create();

        expect($profile->categories()->count())->toBe(3);
    });
});
