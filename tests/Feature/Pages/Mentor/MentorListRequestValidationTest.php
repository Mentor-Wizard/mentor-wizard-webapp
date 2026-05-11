<?php

declare(strict_types=1);

use App\Enums\CurrencyEnum;
use App\Models\Currency;
use App\Models\MentorProfile;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;

describe('MentorListRequest — HTTP validation (B6)', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    it('returns 200 when no query params are provided', function (): void {
        $this->get(route('pages.mentors'))
            ->assertOk();
    });

    it('redirects when experience filter value is invalid', function (): void {
        $this->get(route('pages.mentors', ['filter' => ['experience' => 'beginner']]))
            ->assertRedirect()
            ->assertSessionHasErrors('filter.experience');
    });

    it('redirects when rating filter exceeds 5', function (): void {
        $this->get(route('pages.mentors', ['filter' => ['rating' => 6]]))
            ->assertRedirect()
            ->assertSessionHasErrors('filter.rating');
    });

    it('redirects when rating filter is below 1', function (): void {
        $this->get(route('pages.mentors', ['filter' => ['rating' => 0]]))
            ->assertRedirect()
            ->assertSessionHasErrors('filter.rating');
    });

    it('redirects when sort value is not in allowlist', function (): void {
        $this->get(route('pages.mentors', ['sort' => 'name']))
            ->assertRedirect()
            ->assertSessionHasErrors('sort');
    });

    it('redirects when page is 0', function (): void {
        $this->get(route('pages.mentors', ['page' => 0]))
            ->assertRedirect()
            ->assertSessionHasErrors('page');
    });

    it('redirects when rate.max is less than rate.min', function (): void {
        $this->get(route('pages.mentors', ['filter' => ['rate' => ['min' => 200, 'max' => 100]]]))
            ->assertRedirect()
            ->assertSessionHasErrors('filter.rate.max');
    });

    it('redirects when rate.max exceeds 10000', function (): void {
        $this->get(route('pages.mentors', ['filter' => ['rate' => ['max' => 10001]]]))
            ->assertRedirect()
            ->assertSessionHasErrors('filter.rate.max');
    });

    it('returns 200 with all valid filter params', function (): void {
        $this->get(route('pages.mentors', [
            'filter' => [
                'stacks'     => 'Laravel',
                'languages'  => 'PHP',
                'experience' => 'senior',
                'rating'     => 4,
                'rate'       => ['min' => 50, 'max' => 200],
            ],
            'sort' => '-rate',
            'page' => 1,
        ]))->assertOk();
    });

    it('returns 200 with all valid sort values', function (): void {
        foreach (['id', '-id', 'rate', '-rate', 'experience_started_at', '-experience_started_at'] as $sort) {
            $this->get(route('pages.mentors', ['sort' => $sort]))
                ->assertOk();
        }
    });
});

describe('MentorListRequest — experience null behavior (B9)', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    it('returns null experience when experience_started_at is absent (schema allows null via factory override)', function (): void {
        $mentor = MentorProfile::factory()->create([
            'title'                 => 'Mentor Without Experience Date',
            'experience_started_at' => now()->subYears(5),
        ]);

        DB::statement('ALTER TABLE mentor_profiles ALTER COLUMN experience_started_at DROP NOT NULL');
        DB::table('mentor_profiles')
            ->where('id', $mentor->getKey())
            ->update(['experience_started_at' => null]);

        $this->get(route('pages.mentors'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('mentors.data', 1)
                ->where('mentors.data.0.experience', null)
            );
    });

    it('returns numeric experience years when experience_started_at is set', function (): void {
        MentorProfile::factory()->create([
            'title'                 => 'Senior Mentor',
            'experience_started_at' => now()->subYears(10),
        ]);

        $this->get(route('pages.mentors'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('mentors.data', 1)
                ->where('mentors.data.0.experience', fn ($exp): bool => is_int($exp) && $exp >= 9)
            );
    });

    it('does not include availability or availabilityLabel keys in mentor data (B4)', function (): void {
        MentorProfile::factory()->create(['title' => 'Test Mentor']);

        $this->get(route('pages.mentors'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('mentors.data', 1)
                ->missing('mentors.data.0.availability')
                ->missing('mentors.data.0.availabilityLabel')
            );
    });
});

describe('MentorListRequest — RateInUsdSort NULL currency (B3)', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        MentorProfile::query()->delete();
    });

    it('includes mentor with NULL currency_id in sorted results', function (): void {
        $mentor = MentorProfile::factory()->create([
            'title' => 'No Currency Mentor',
            'rate'  => 50.0,
        ]);

        DB::statement('ALTER TABLE mentor_profiles ALTER COLUMN currency_id DROP NOT NULL');
        DB::table('mentor_profiles')
            ->where('id', $mentor->getKey())
            ->update(['currency_id' => null]);

        $this->get(route('pages.mentors', ['sort' => 'rate']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('mentors.data', 1)
                ->where('mentors.data.0.title', 'No Currency Mentor')
            );
    });

    it('sorts mentors with and without currency correctly', function (): void {
        $usd = Currency::query()->firstOrCreate(
            ['name' => CurrencyEnum::USD->name],
            ['symbol' => CurrencyEnum::USD->value, 'exchange_rate' => CurrencyEnum::USD->exchangeRate()],
        );

        $mentorWithCurrency = MentorProfile::factory()->create([
            'title'       => 'Currency Mentor',
            'rate'        => 100.0,
            'currency_id' => $usd->getKey(),
        ]);

        $mentorNoCurrency = MentorProfile::factory()->create([
            'title' => 'No Currency Mentor',
            'rate'  => 50.0,
        ]);

        DB::statement('ALTER TABLE mentor_profiles ALTER COLUMN currency_id DROP NOT NULL');
        DB::table('mentor_profiles')
            ->where('id', $mentorNoCurrency->getKey())
            ->update(['currency_id' => null]);

        $this->get(route('pages.mentors', ['sort' => 'rate']))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
                ->has('mentors.data', 2)
                ->where('mentors.data.0.title', 'No Currency Mentor')
                ->where('mentors.data.1.title', 'Currency Mentor')
            );
    });
});
