<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Jobs\ProcessMentorProgramAnalytics;
use App\Models\MentorProgram;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;

mutates(ProcessMentorProgramAnalytics::class);

describe('ProcessMentorProgramAnalytics Job', function (): void {
    beforeEach(function (): void {
        // Створюємо роль для UserObserver
        Role::create(['name' => RoleEnum::USER]);

        $this->user = User::factory()->create();
        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->user->getKey(),
        ]);
    });

    it('processes analytics successfully', function (): void {
        $job = new ProcessMentorProgramAnalytics(
            $this->mentorProgram->getKey(),
            ['test' => 'data']
        );

        $job->handle();

        $cacheKey = 'mentor_program_analytics_'.$this->mentorProgram->getKey();
        expect(Cache::has($cacheKey))->toBeTrue();
    });

    it('stores processed metrics in cache', function (): void {
        $job = new ProcessMentorProgramAnalytics(
            $this->mentorProgram->getKey(),
            ['source' => 'test']
        );

        $job->handle();

        $cacheKey = 'mentor_program_analytics_'.$this->mentorProgram->getKey();
        $metrics = Cache::get($cacheKey);

        expect($metrics)
            ->toBeArray()
            ->toHaveKeys(['total_views', 'engagement_rate', 'conversion_rate', 'daily_stats', 'program_info'])
            ->and($metrics['program_info']['id'])
            ->toBe($this->mentorProgram->getKey())
            ->and($metrics['daily_stats'])
            ->toBeArray()
            ->toHaveCount(7);
    });

    it('calculates metrics correctly', function (): void {
        $job = new ProcessMentorProgramAnalytics(
            $this->mentorProgram->getKey(),
            []
        );

        $job->handle();

        $cacheKey = 'mentor_program_analytics_'.$this->mentorProgram->getKey();
        $metrics = Cache::get($cacheKey);

        expect($metrics['total_views'])->toBeGreaterThan(0)
            ->and($metrics['engagement_rate'])->toBeFloat()
            ->and($metrics['conversion_rate'])->toBeFloat();
    });

    it('includes daily statistics', function (): void {
        $job = new ProcessMentorProgramAnalytics(
            $this->mentorProgram->getKey(),
            []
        );

        $job->handle();

        $cacheKey = 'mentor_program_analytics_'.$this->mentorProgram->getKey();
        $metrics = Cache::get($cacheKey);

        foreach ($metrics['daily_stats'] as $stat) {
            expect($stat)
                ->toHaveKeys(['date', 'views', 'growth_rate', 'engagement'])
                ->and($stat['date'])
                ->toBeString()
                ->and($stat['views'])
                ->toBeInt()
                ->and($stat['growth_rate'])
                ->toBeNumeric();
        }
    });

    it('handles job with retries configured', function (): void {
        $job = new ProcessMentorProgramAnalytics(
            $this->mentorProgram->getKey(),
            []
        );

        expect($job->tries)->toBe(3)
            ->and($job->backoff)->toBe(5);
    });
});
