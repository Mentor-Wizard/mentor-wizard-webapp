<?php

declare(strict_types=1);

use App\Actions\Pages\Profile\ListMentorProfilePage;
use App\Enums\TagEnum;
use App\Models\MentorProfile;
use App\Models\MentorProgram;
use App\Models\MentorReview;
use App\Models\MentorTag;
use Database\Seeders\RoleSeeder;

mutates(ListMentorProfilePage::class);

describe('ListMentorProfilePage filters and includes', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        // Create profiles
        $this->profileA = MentorProfile::factory()->create(['title' => 'Laravel Guru', 'description' => 'Senior dev', 'rate' => 80.0]);
        $this->profileB = MentorProfile::factory()->create(['title' => 'React Ninja', 'description' => 'Frontend pro', 'rate' => 75.0]);
        $this->profileC = MentorProfile::factory()->create(['title' => 'Python Master', 'description' => 'Monty Guy', 'rate' => 40.0]);

        // Programs
        $this->programA1 = MentorProgram::factory()->create(['name' => 'Advanced PHP', 'description' => 'PHP', 'cost' => 600]);
        $this->programB1 = MentorProgram::factory()->create(['name' => 'React Basics', 'description' => 'React', 'cost' => 200]);
        $this->programC1 = MentorProgram::factory()->create(['name' => 'Python Junior', 'description' => 'Python', 'cost' => 50]);

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
        $resp = $this->getJson(route('page.profile-programs'));
        $resp->assertOk();

        $data = $resp->json('data');
        expect($data)->toBeArray()->and(count($data))->toBe(3);
    });

    it('filters by title and description', function (): void {
        $this->getJson(route('page.profile-programs', ['filter' => ['title' => 'Laravel']]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['title' => 'Laravel Guru']);

        $this->getJson(route('page.profile-programs', ['filter' => ['description' => 'Frontend']]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['title' => 'React Ninja']);
    });

    it('filters by mentor program fields', function (): void {
        $this->getJson(route('page.profile-programs', ['filter' => ['mentorPrograms.name' => 'Advanced'], 'include' => 'mentorPrograms']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'Advanced PHP']);
        $this->getJson(route('page.profile-programs', ['filter' => ['mentorPrograms.description' => 'Python'], 'include' => 'mentorPrograms']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'Python Junior']);
    });

    it('filters by rate range using array params', function (): void {
        $this->getJson(route('page.profile-programs', ['filter' => ['rate' => ['min' => 50, 'max' => 90]]]))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['title' => 'Laravel Guru'])
            ->assertJsonFragment(['title' => 'React Ninja']);
        $this->getJson(route('page.profile-programs', ['filter' => ['rate' => ['min' => 0, 'max' => 50]]]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['title' => 'Python Master']);

    });

    it('filters by cost range via related mentorPrograms using array params', function (): void {
        $this->getJson(route('page.profile-programs', ['filter' => ['cost' => ['min' => 0, 'max' => 300]]]))
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson(route('page.profile-programs', ['filter' => ['cost' => ['min' => 500, 'max' => 700]]]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['title' => 'Laravel Guru']);

        $this->getJson(route('page.profile-programs', ['filter' => ['cost' => ['min' => 0, 'max' => 300]]]))
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('filters by languages and stacks', function (): void {
        $this->getJson(route('page.profile-programs', ['filter' => ['languages' => 'PHP']]))
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson(route('page.profile-programs', ['filter' => ['stacks' => 'Laravel']]))
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('supports includes for mentorPrograms and mentorTags', function (): void {
        $resp = $this->getJson(route('page.profile-programs', ['include' => 'mentorPrograms,mentorTags']));
        $resp->assertOk();

        $first = $resp->json('data.0');
        expect($first)->toHaveKeys(['mentor_programs', 'mentor_tags']);
    });

    it('supports sorting by rate desc', function (): void {
        $resp = $this->getJson(route('page.profile-programs', ['sort' => '-rate']));
        $resp->assertOk();

        $titles = array_column($resp->json('data'), 'title');
        expect($titles)->toBe(['Laravel Guru', 'React Ninja', 'Python Master']);
    });

    it('supports sorting by id desc', function (): void {
        $resp = $this->getJson(route('page.profile-programs', ['sort' => '-id']));
        $resp->assertOk();

        $titles = array_column($resp->json('data'), 'title');
        expect($titles)->toBe(['Python Master', 'React Ninja', 'Laravel Guru']);
    });

    it('supports sorting by experience_started_at asc', function (): void {
        $resp = $this->getJson(route('page.profile-programs', ['sort' => 'experience_started_at']));
        $resp->assertOk();

        $titles = array_column($resp->json('data'), 'title');
        expect($titles)->toBe(['Python Master', 'Laravel Guru', 'React Ninja']);
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

        $this->getJson(route('page.profile-programs', ['filter' => ['experience' => 'entry']]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['title' => 'Entry Level Dev']);

        $this->getJson(route('page.profile-programs', ['filter' => ['experience' => 'mid']]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['title' => 'Mid Level Dev']);

        $this->getJson(route('page.profile-programs', ['filter' => ['experience' => 'senior']]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['title' => 'Senior Dev']);

        $this->getJson(route('page.profile-programs', ['filter' => ['experience' => 'expert']]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['title' => 'Expert Dev']);
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

        $this->getJson(route('page.profile-programs', ['filter' => ['experience' => 'mid,senior']]))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['title' => 'Mid Level Dev'])
            ->assertJsonFragment(['title' => 'Senior Dev']);

        $this->getJson(route('page.profile-programs', ['filter' => ['experience' => 'entry,expert']]))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['title' => 'Entry Level Dev'])
            ->assertJsonFragment(['title' => 'Expert Dev']);
    });

    it('filters by minimum rating', function (): void {
        MentorProfile::query()->delete();

        $mentor1 = MentorProfile::factory()->create(['title' => 'Low Rated Mentor']);
        $mentor2 = MentorProfile::factory()->create(['title' => 'Good Rated Mentor']);
        $mentor3 = MentorProfile::factory()->create(['title' => 'Excellent Rated Mentor']);
        $mentor4 = MentorProfile::factory()->create(['title' => 'No Rating Mentor']);

        MentorReview::factory()->create(['mentor_id' => $mentor1->user_id, 'rating' => 2]);
        MentorReview::factory()->create(['mentor_id' => $mentor1->user_id, 'rating' => 3]);

        MentorReview::factory()->create(['mentor_id' => $mentor2->user_id, 'rating' => 4]);
        MentorReview::factory()->create(['mentor_id' => $mentor2->user_id, 'rating' => 4]);

        MentorReview::factory()->create(['mentor_id' => $mentor3->user_id, 'rating' => 5]);
        MentorReview::factory()->create(['mentor_id' => $mentor3->user_id, 'rating' => 5]);
        MentorReview::factory()->create(['mentor_id' => $mentor3->user_id, 'rating' => 4]);

        $this->getJson(route('page.profile-programs', ['filter' => ['rating' => 4]]))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['title' => 'Good Rated Mentor'])
            ->assertJsonFragment(['title' => 'Excellent Rated Mentor']);

        $this->getJson(route('page.profile-programs', ['filter' => ['rating' => 4.5]]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['title' => 'Excellent Rated Mentor']);

        $this->getJson(route('page.profile-programs', ['filter' => ['rating' => 3]]))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['title' => 'Good Rated Mentor'])
            ->assertJsonFragment(['title' => 'Excellent Rated Mentor']);
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

        $this->getJson(route('page.profile-programs', ['filter' => ['experience' => 'senior', 'rating' => 4]]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['title' => 'Senior High Rated']);

        $this->getJson(route('page.profile-programs', ['filter' => ['experience' => 'mid,senior', 'rating' => 4.5]]))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['title' => 'Senior High Rated'])
            ->assertJsonFragment(['title' => 'Mid High Rated']);
    });
});
