<?php

declare(strict_types=1);

use Database\Seeders\RoleSeeder;
use Modules\Marketplace\Filters\ProfileRateFilter;
use Modules\Marketplace\Models\MentorProfile;

covers(ProfileRateFilter::class);

describe('ProfileRateFilter Integration', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->filter = new ProfileRateFilter;

        MentorProfile::query()->delete();
        $this->profileLow = MentorProfile::factory()->create(['rate' => 25.0]);
        $this->profileMid = MentorProfile::factory()->create(['rate' => 50.0]);
        $this->profileHigh = MentorProfile::factory()->create(['rate' => 75.0]);
    });

    it('handles swapped min/max values correctly', function (): void {
        $query = MentorProfile::query();
        $this->filter->__invoke($query, ['min' => '60', 'max' => '40'], 'rate');
        $results = $query->get();

        expect($results)->toHaveCount(1)
            ->and((float) $results->first()->rate)->toBe(50.0);
    });

    it('filters with equal min/max values', function (): void {
        $query = MentorProfile::query();
        $this->filter->__invoke($query, ['min' => '50', 'max' => '50'], 'rate');
        $results = $query->get();

        expect($results)->toHaveCount(1)
            ->and((float) $results->first()->rate)->toBe(50.0);
    });

    it('filters with range values correctly', function (): void {
        $query = MentorProfile::query();
        $this->filter->__invoke($query, ['min' => '30', 'max' => '80'], 'rate');
        $results = $query->get();

        expect($results)->toHaveCount(2);
        $rates = $results->pluck('rate')->map(fn ($rate): float => (float) $rate)->sort()->values();
        expect($rates->all())->toBe([50.0, 75.0]);
    });

    it('filters with min-only and max-only values', function (): void {
        $query1 = MentorProfile::query();
        $this->filter->__invoke($query1, ['min' => '50'], 'rate');
        $results1 = $query1->get();

        expect($results1)->toHaveCount(2);
        $rates1 = $results1->pluck('rate')->map(fn ($rate): float => (float) $rate)->sort()->values();
        expect($rates1->all())->toBe([50.0, 75.0]);

        $query2 = MentorProfile::query();
        $this->filter->__invoke($query2, ['max' => '50'], 'rate');
        $results2 = $query2->get();

        expect($results2)->toHaveCount(2);
        $rates2 = $results2->pluck('rate')->map(fn ($rate): float => (float) $rate)->sort()->values();
        expect($rates2->all())->toBe([25.0, 50.0]);
    });

    it('generates correct SQL with BETWEEN clause', function (): void {
        $query = MentorProfile::query();
        $this->filter->__invoke($query, ['min' => '50.0', 'max' => '50.0'], 'rate');

        $sql = $query->toSql();
        $bindings = $query->getBindings();

        expect($sql)->toContain('between ? and ?')
            ->and($bindings)->toBe([50.0, 50.0])
            ->and($query->get())->toHaveCount(1)
            ->and((float) $query->get()->first()->rate)->toBe(50.0);
    });
});
