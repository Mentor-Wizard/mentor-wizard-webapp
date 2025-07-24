<?php

declare(strict_types=1);

use App\Enums\TagEnum;
use App\Models\Currency;
use App\Models\MentorProfile;
use App\Models\MentorTag;
use App\Models\User;
use Database\Seeders\RoleSeeder;

covers(MentorProfile::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('can create a mentor profile', function (): void {
    $user = User::factory()->create();
    $profile = MentorProfile::factory()->create(['user_id' => $user->id]);

    expect($profile)->toBeInstanceOf(MentorProfile::class)
        ->and($profile->user_id)->toBe($user->id);
});


it('has the correct fillable attributes', function (): void {
    $model = new MentorProfile;
    expect($model->getFillable())->toEqual([
        'user_id',
        'title',
        'description',
        'rate',
        'currency_id',
        'experience_started_at',
    ]);
});

it('has currency relationship', function (): void {
    $currency = Currency::factory()->create();
    $profile = MentorProfile::factory()->create([
        'currency_id' => $currency->id,
    ]);

    expect($profile->currency)->not->toBeNull()
        ->and($profile->currency->id)->toEqual($currency->id);
});

it('belongs to a user', function () {
    $user = User::factory()->create();
    $profile = MentorProfile::factory()->create(['user_id' => $user->id]);

    expect($profile->user)->toBeInstanceOf(User::class)
        ->and($profile->user->is($user))->toBeTrue();
});

it('returns related mentor tags', function () {
    $profile = MentorProfile::factory()->create();
    $tag = MentorTag::factory()->create();

    $profile->mentorTags()->attach($tag);

    expect($profile->mentorTags)->toHaveCount(1)
        ->and($profile->mentorTags->first()->is($tag))->toBeTrue();
});

it('returns only language tags', function () {
    $profile = MentorProfile::factory()->create();

    $langTag = MentorTag::factory()->create(['type' => TagEnum::LANGUAGE]);
    $stackTag = MentorTag::factory()->create(['type' => TagEnum::STACK]);

    $profile->mentorTags()->attach([$langTag->id, $stackTag->id]);

    expect($profile->languages)->toHaveCount(1)
        ->and($profile->languages->first()->is($langTag))->toBeTrue();
});

it('returns only stack tags', function () {
    $profile = MentorProfile::factory()->create();

    $langTag = MentorTag::factory()->create(['type' => TagEnum::LANGUAGE]);
    $stackTag = MentorTag::factory()->create(['type' => TagEnum::STACK]);

    $profile->mentorTags()->attach([$langTag->id, $stackTag->id]);

    expect($profile->stacks)->toHaveCount(1)
        ->and($profile->stacks->first()->is($stackTag))->toBeTrue();
});


