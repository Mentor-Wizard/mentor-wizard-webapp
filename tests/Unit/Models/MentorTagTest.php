<?php

declare(strict_types=1);

use App\Enums\TagEnum;
use App\Models\MentorProfile;
use App\Models\MentorTag;
use Database\Seeders\RoleSeeder;

covers(MentorTag::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('is related to mentor profiles', function (): void {
    $tag = MentorTag::factory()->create();
    $profile = MentorProfile::factory()->create();

    $tag->mentorProfiles()->attach($profile);

    expect($tag->mentorProfiles)->toHaveCount(1)
        ->and($tag->mentorProfiles->first()->is($profile))->toBeTrue();
});


it('casts type field to TagEnum', function (): void {
    $tag = MentorTag::factory()->create([
        'type' => TagEnum::LANGUAGE,
    ]);

    expect($tag->type)->toBeInstanceOf(TagEnum::class)
        ->and($tag->type)->toBe(TagEnum::LANGUAGE);
});