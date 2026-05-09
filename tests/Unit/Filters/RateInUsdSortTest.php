<?php

declare(strict_types=1);

use App\Filters\RateInUsdSort;
use App\Models\Currency;
use App\Models\MentorProfile;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\Sorts\Sort;

mutates(RateInUsdSort::class);

describe('RateInUsdSort', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->sort = new RateInUsdSort;

        MentorProfile::query()->delete();
    });

    it('implements Sort interface', function (): void {
        expect($this->sort)->toBeInstanceOf(Sort::class);
    });

    it('includes mentors with NULL currency_id instead of excluding them', function (): void {
        $mentor = MentorProfile::factory()->create(['rate' => 50.0]);

        DB::statement(
            'ALTER TABLE mentor_profiles ALTER COLUMN currency_id DROP NOT NULL'
        );
        DB::table('mentor_profiles')
            ->where('id', $mentor->getKey())
            ->update(['currency_id' => null]);

        $query = MentorProfile::query();
        $this->sort->__invoke($query, false, 'rate');

        $results = $query->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->getKey())->toBe($mentor->getKey());
    });

    it('sorts ascending by USD equivalent rate across currencies', function (): void {
        $usd = Currency::factory()->create(['name' => 'USD', 'symbol' => '$', 'exchange_rate' => 1.0]);
        $eur = Currency::factory()->create(['name' => 'EUR', 'symbol' => '€', 'exchange_rate' => 1.08]);
        $uah = Currency::factory()->create(['name' => 'UAH', 'symbol' => '₴', 'exchange_rate' => 0.024]);

        $usdMentor = MentorProfile::factory()->create([
            'title'       => 'USD Mentor',
            'rate'        => 30.0,
            'currency_id' => $usd->getKey(),
        ]);

        $eurMentor = MentorProfile::factory()->create([
            'title'       => 'EUR Mentor',
            'rate'        => 50.0,
            'currency_id' => $eur->getKey(),
        ]);

        $uahMentor = MentorProfile::factory()->create([
            'title'       => 'UAH Mentor',
            'rate'        => 1000.0,
            'currency_id' => $uah->getKey(),
        ]);

        $query = MentorProfile::query();
        $this->sort->__invoke($query, false, 'rate');

        $results = $query->get();

        expect($results->pluck('title')->toArray())->toBe(['UAH Mentor', 'USD Mentor', 'EUR Mentor']);
    });

    it('sorts descending by USD equivalent rate across currencies', function (): void {
        $usd = Currency::factory()->create(['name' => 'USD', 'symbol' => '$', 'exchange_rate' => 1.0]);
        $eur = Currency::factory()->create(['name' => 'EUR', 'symbol' => '€', 'exchange_rate' => 1.08]);
        $uah = Currency::factory()->create(['name' => 'UAH', 'symbol' => '₴', 'exchange_rate' => 0.024]);

        MentorProfile::factory()->create([
            'title'       => 'USD Mentor',
            'rate'        => 30.0,
            'currency_id' => $usd->getKey(),
        ]);

        MentorProfile::factory()->create([
            'title'       => 'EUR Mentor',
            'rate'        => 50.0,
            'currency_id' => $eur->getKey(),
        ]);

        MentorProfile::factory()->create([
            'title'       => 'UAH Mentor',
            'rate'        => 1000.0,
            'currency_id' => $uah->getKey(),
        ]);

        $query = MentorProfile::query();
        $this->sort->__invoke($query, true, 'rate');

        $results = $query->get();

        expect($results->pluck('title')->toArray())->toBe(['EUR Mentor', 'USD Mentor', 'UAH Mentor']);
    });

    it('generates SQL with COALESCE for NULL-safe rate and exchange_rate', function (): void {
        $usd = Currency::factory()->create(['name' => 'USD', 'symbol' => '$', 'exchange_rate' => 1.0]);

        MentorProfile::factory()->create([
            'rate'        => 100.0,
            'currency_id' => $usd->getKey(),
        ]);

        $query = MentorProfile::query();
        $this->sort->__invoke($query, false, 'rate');

        $sql = $query->toRawSql();

        expect(mb_strtolower($sql))->toContain('coalesce(mentor_profiles.rate, 0)')
            ->and(mb_strtolower($sql))->toContain('coalesce(currencies.exchange_rate, 1)');
    });

    it('selects only mentor_profiles columns to avoid ambiguous id', function (): void {
        $usd = Currency::factory()->create(['name' => 'USD', 'symbol' => '$', 'exchange_rate' => 1.0]);

        MentorProfile::factory()->create([
            'rate'        => 50.0,
            'currency_id' => $usd->getKey(),
        ]);

        $query = MentorProfile::query();
        $this->sort->__invoke($query, false, 'rate');

        expect(fn () => $query->get())->not->toThrow(Throwable::class);
    });

    it('uses leftJoin so all mentors are included regardless of currency', function (): void {
        Currency::factory()->create(['name' => 'USD', 'symbol' => '$', 'exchange_rate' => 1.0]);
        $mentorWithCurrency = MentorProfile::factory()->create(['rate' => 50.0]);

        DB::statement(
            'ALTER TABLE mentor_profiles ALTER COLUMN currency_id DROP NOT NULL'
        );

        $mentorNoCurrency = MentorProfile::factory()->create(['rate' => 100.0]);
        DB::table('mentor_profiles')
            ->where('id', $mentorNoCurrency->getKey())
            ->update(['currency_id' => null]);

        $query = MentorProfile::query();
        $this->sort->__invoke($query, false, 'rate');

        $results = $query->get();

        expect($results)->toHaveCount(2)
            ->and($results->pluck('id')->toArray())->toContain($mentorWithCurrency->getKey())
            ->and($results->pluck('id')->toArray())->toContain($mentorNoCurrency->getKey());
    });
});
