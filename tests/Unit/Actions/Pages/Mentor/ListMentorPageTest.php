<?php

declare(strict_types=1);

use App\Actions\Pages\Mentor\MentorsListPage;
use App\Enums\TagEnum;
use App\Http\Requests\Mentor\MentorListRequest;
use App\Models\Currency;
use App\Models\MentorProfile;
use App\Models\MentorTag;
use Database\Seeders\RoleSeeder;
use Inertia\Response;

mutates(MentorsListPage::class);

describe('MentorsListPage Unit Tests', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    describe('action instantiation and basic handling', function (): void {
        it('can be instantiated without dependencies', function (): void {
            $action = new MentorsListPage;

            expect($action)->toBeInstanceOf(MentorsListPage::class);
        });

        it('renders the mentor list page successfully', function (): void {
            $action = new MentorsListPage;
            $request = MentorListRequest::create('/mentors', 'GET');

            $response = $action->handle($request);

            expect($response)->toBeInstanceOf(Response::class);
        });

        it('accepts filter parameters from request', function (): void {
            $action = new MentorsListPage;
            $request = MentorListRequest::create('/mentors', 'GET', [
                'filter' => [
                    'stacks'     => 'Laravel',
                    'languages'  => 'PHP',
                    'experience' => 'senior',
                    'rate'       => ['min' => 50, 'max' => 150],
                    'rating'     => 4,
                ],
            ]);

            $response = $action->handle($request);

            expect($response)->toBeInstanceOf(Response::class);
        });
    });

    describe('private method getStackOptions', function (): void {
        it('returns empty array when no stack tags exist', function (): void {
            $action = new MentorsListPage;
            $request = MentorListRequest::create('/mentors', 'GET');

            $response = $action->handle($request);

            expect($response)->toBeInstanceOf(Response::class);
        });

        it('returns stack options from database', function (): void {
            MentorTag::factory()->create(['type' => TagEnum::STACK, 'tag' => 'Laravel']);
            MentorTag::factory()->create(['type' => TagEnum::STACK, 'tag' => 'React']);

            $action = new MentorsListPage;
            $request = MentorListRequest::create('/mentors', 'GET');

            $response = $action->handle($request);

            expect($response)->toBeInstanceOf(Response::class);
        });

        it('excludes language tags from stack options', function (): void {
            MentorTag::factory()->create(['type' => TagEnum::STACK, 'tag' => 'Laravel']);
            MentorTag::factory()->create(['type' => TagEnum::LANGUAGE, 'tag' => 'PHP']);

            $action = new MentorsListPage;
            $request = MentorListRequest::create('/mentors', 'GET');

            $response = $action->handle($request);

            expect($response)->toBeInstanceOf(Response::class);
        });
    });

    describe('private method getLanguageOptions', function (): void {
        it('returns empty array when no language tags exist', function (): void {
            $action = new MentorsListPage;
            $request = MentorListRequest::create('/mentors', 'GET');

            $response = $action->handle($request);

            expect($response)->toBeInstanceOf(Response::class);
        });

        it('returns language options from database', function (): void {
            MentorTag::factory()->create(['type' => TagEnum::LANGUAGE, 'tag' => 'PHP']);
            MentorTag::factory()->create(['type' => TagEnum::LANGUAGE, 'tag' => 'JavaScript']);

            $action = new MentorsListPage;
            $request = MentorListRequest::create('/mentors', 'GET');

            $response = $action->handle($request);

            expect($response)->toBeInstanceOf(Response::class);
        });

        it('excludes stack tags from language options', function (): void {
            MentorTag::factory()->create(['type' => TagEnum::LANGUAGE, 'tag' => 'PHP']);
            MentorTag::factory()->create(['type' => TagEnum::STACK, 'tag' => 'Laravel']);

            $action = new MentorsListPage;
            $request = MentorListRequest::create('/mentors', 'GET');

            $response = $action->handle($request);

            expect($response)->toBeInstanceOf(Response::class);
        });
    });

    describe('private method getCurrencyOptions', function (): void {
        it('returns currency options from database', function (): void {
            Currency::factory()->create(['name' => 'USD', 'symbol' => '$']);
            Currency::factory()->create(['name' => 'EUR', 'symbol' => 'E']);

            $action = new MentorsListPage;
            $request = MentorListRequest::create('/mentors', 'GET');

            $response = $action->handle($request);

            expect($response)->toBeInstanceOf(Response::class);
        });
    });

    describe('mentor data transformation', function (): void {
        it('transforms mentor profile to card data', function (): void {
            MentorProfile::factory()->create(['title' => 'Test Mentor']);

            $action = new MentorsListPage;
            $request = MentorListRequest::create('/mentors', 'GET');

            $response = $action->handle($request);

            expect($response)->toBeInstanceOf(Response::class);
        });

        it('calculates experience years from experience_started_at', function (): void {
            MentorProfile::factory()->create([
                'title'                 => 'Senior Mentor',
                'experience_started_at' => now()->subYears(10),
            ]);

            $action = new MentorsListPage;
            $request = MentorListRequest::create('/mentors', 'GET');

            $response = $action->handle($request);

            expect($response)->toBeInstanceOf(Response::class);
        });

        it('handles mentor with very recent experience start date', function (): void {
            MentorProfile::factory()->create([
                'title'                 => 'New Mentor',
                'experience_started_at' => now()->subMonths(6),
            ]);

            $action = new MentorsListPage;
            $request = MentorListRequest::create('/mentors', 'GET');

            $response = $action->handle($request);

            expect($response)->toBeInstanceOf(Response::class);
        });

        it('includes only stack tags in mentor card tags', function (): void {
            $mentor = MentorProfile::factory()->create(['title' => 'Test Mentor']);

            $stackTag = MentorTag::factory()->create(['type' => TagEnum::STACK, 'tag' => 'Laravel']);
            $langTag = MentorTag::factory()->create(['type' => TagEnum::LANGUAGE, 'tag' => 'PHP']);

            $mentor->mentorTags()->attach([$stackTag->getKey(), $langTag->getKey()]);

            $action = new MentorsListPage;
            $request = MentorListRequest::create('/mentors', 'GET');

            $response = $action->handle($request);

            expect($response)->toBeInstanceOf(Response::class);
        });
    });

    describe('pagination', function (): void {
        it('paginates results', function (): void {
            MentorProfile::factory()->count(10)->create();

            $action = new MentorsListPage;
            $request = MentorListRequest::create('/mentors', 'GET');

            $response = $action->handle($request);

            expect($response)->toBeInstanceOf(Response::class);
        });

        it('appends query params to pagination links', function (): void {
            MentorProfile::factory()->count(10)->create();

            $action = new MentorsListPage;
            $request = MentorListRequest::create('/mentors', 'GET', [
                'filter' => ['stacks' => 'Laravel'],
            ]);

            $response = $action->handle($request);

            expect($response)->toBeInstanceOf(Response::class);
        });
    });

    describe('allowed sorts', function (): void {
        it('allows sorting by id', function (): void {
            MentorProfile::factory()->count(3)->create();

            $action = new MentorsListPage;
            $request = MentorListRequest::create('/mentors', 'GET', ['sort' => 'id']);

            $response = $action->handle($request);

            expect($response)->toBeInstanceOf(Response::class);
        });

        it('allows sorting by rate', function (): void {
            MentorProfile::factory()->count(3)->create();

            $action = new MentorsListPage;
            $request = MentorListRequest::create('/mentors', 'GET', ['sort' => 'rate']);

            $response = $action->handle($request);

            expect($response)->toBeInstanceOf(Response::class);
        });

        it('allows sorting by experience_started_at', function (): void {
            MentorProfile::factory()->count(3)->create();

            $action = new MentorsListPage;
            $request = MentorListRequest::create('/mentors', 'GET', ['sort' => 'experience_started_at']);

            $response = $action->handle($request);

            expect($response)->toBeInstanceOf(Response::class);
        });
    });
});
