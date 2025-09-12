<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Filters\ProgramCostFilter;
use App\Models\MentorProfile;
use App\Models\MentorProgram;
use App\Models\User;
use Database\Seeders\RoleSeeder;

covers(ProgramCostFilter::class);

describe('ProgramCostFilter Integration Tests', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->filter = new ProgramCostFilter;

        $mentors = User::factory(3)->create()->each(fn ($mentor) => $mentor->assignRole(RoleEnum::MENTOR));

        $this->profiles = collect([
            MentorProfile::factory()->create(['user_id' => $mentors[0]->id]),
            MentorProfile::factory()->create(['user_id' => $mentors[1]->id]),
            MentorProfile::factory()->create(['user_id' => $mentors[2]->id]),
        ]);

        $programs = collect([
            MentorProgram::factory()->create(['mentor_id' => $mentors[0]->id, 'cost' => 30.0]),
            MentorProgram::factory()->create(['mentor_id' => $mentors[1]->id, 'cost' => 60.0]),
            MentorProgram::factory()->create(['mentor_id' => $mentors[2]->id, 'cost' => 90.0]),
        ]);

        $this->profiles->each(fn ($profile, $index) => $profile->mentorPrograms()->attach($programs[$index]->id));
    });

    it('filters profiles within cost range', function (): void {
        $query = MentorProfile::query();
        $this->filter->__invoke($query, ['min' => 25, 'max' => 65], 'cost');
        $profiles = $query->get();

        expect($profiles)->toHaveCount(2);
        expect($profiles->pluck('id')->toArray())->toContain($this->profiles[0]->id, $this->profiles[1]->id);
        expect($profiles->pluck('id')->toArray())->not->toContain($this->profiles[2]->id);
    });

    it('filters profiles by min and max cost bounds', function (): void {
        $query1 = MentorProfile::query();
        $this->filter->__invoke($query1, ['min' => 50], 'cost');
        $profiles1 = $query1->get();

        expect($profiles1)->toHaveCount(2);
        expect($profiles1->pluck('id')->toArray())->toContain($this->profiles[1]->id, $this->profiles[2]->id);

        $query2 = MentorProfile::query();
        $this->filter->__invoke($query2, ['max' => 70], 'cost');
        $profiles2 = $query2->get();

        expect($profiles2)->toHaveCount(2);
        expect($profiles2->pluck('id')->toArray())->toContain($this->profiles[0]->id, $this->profiles[1]->id);
    });

    it('handles swapped min/max values correctly', function (): void {
        $query = MentorProfile::query();
        $this->filter->__invoke($query, ['min' => 70, 'max' => 40], 'cost');
        $profiles = $query->get();

        expect($profiles)->toHaveCount(1);
        expect($profiles->pluck('id')->toArray())->toContain($this->profiles[1]->id);
        expect($profiles->pluck('id')->toArray())->not->toContain($this->profiles[0]->id, $this->profiles[2]->id);
    });

    it('handles edge cases correctly', function (): void {
        $testCases = [
            [[], 3], // no bounds
            ['invalid', 3], // invalid input
            [['min' => null, 'max' => null], 3], // null values
            [['min' => 30, 'max' => 60], 2], // exact boundaries
            [['min' => 200, 'max' => 300], 0], // empty result
        ];

        foreach ($testCases as [$input, $expectedCount]) {
            $query = MentorProfile::query();
            $this->filter->__invoke($query, $input, 'cost');
            expect($query->get())->toHaveCount($expectedCount);
        }
    });
});
