<?php

declare(strict_types=1);

use App\Actions\Pages\Profile\ListMentorProfilePage;
use App\Enums\TagEnum;
use App\Models\MentorProfile;
use App\Models\MentorProgram;
use App\Models\MentorTag;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Route;

mutates(ListMentorProfilePage::class);

describe('ListMentorProfilePage unit-ish filter coverage', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        Route::get('/unit/mentor-profiles', ListMentorProfilePage::class);

        $this->p1 = MentorProfile::factory()->create(['title' => 'A', 'rate' => 75]);
        $this->p2 = MentorProfile::factory()->create(['title' => 'B', 'rate' => 90]);

        $this->prog1 = MentorProgram::factory()->create(['name' => 'Alpha', 'cost' => 150]);
        $this->prog2 = MentorProgram::factory()->create(['name' => 'Beta', 'cost' => 350]);
        $this->p1->mentorPrograms()->attach($this->prog1->getKey());
        $this->p2->mentorPrograms()->attach($this->prog2->getKey());

        $this->langPhp = MentorTag::factory()->create(['type' => TagEnum::LANGUAGE, 'tag' => 'PHP']);
        $this->stackLar = MentorTag::factory()->create(['type' => TagEnum::STACK, 'tag' => 'Laravel']);
        $this->p1->mentorTags()->attach([$this->langPhp->getKey(), $this->stackLar->getKey()]);
    });

    it('filters by languages with array syntax', function (): void {
        $this->getJson('/unit/mentor-profiles?filter[languages]=PHP')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/unit/mentor-profiles?filter[languages]=PHP,Go')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('filters by stacks with single value', function (): void {
        $this->getJson('/unit/mentor-profiles?filter[stacks]=Laravel')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/unit/mentor-profiles?filter[stacks]=Laravel,Symfony')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('filters by explicit rate and cost ranges', function (): void {
        // rate: only max provided
        $this->getJson('/unit/mentor-profiles?filter[rate][max]=80')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        // rate: min & max
        $this->getJson('/unit/mentor-profiles?filter[rate][min]=70&filter[rate][max]=95')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        // cost via related mentorPrograms
        $this->getJson('/unit/mentor-profiles?filter[cost][min]=100&filter[cost][max]=200')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });
});
