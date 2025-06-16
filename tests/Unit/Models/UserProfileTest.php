<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\Support\PathGenerator\PathGeneratorFactory;

covers(UserProfile::class);

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    Storage::fake('public');
});

it('can create a user profile', function (): void {
    $user = User::factory()->create();

    $profile = UserProfile::factory()->create(['user_id' => $user->id]);

    expect($profile)->toBeInstanceOf(UserProfile::class)
        ->and($profile->user_id)->toBe($user->id);
});

it('belongs to the user', function (): void {
    $user = User::factory()->create();
    $profile = UserProfile::factory()->create(['user_id' => $user->id]);

    expect($profile->user)->toBeInstanceOf(User::class)
        ->and($profile->user->id)->toBe($user->id);
});

it('successfully upload an image', function (): void {
    Storage::fake('public');
    $file = UploadedFile::fake()->image('test-image.jpg', 600, 400);

    $user = User::factory()->create();
    $user->profile->addMedia($file)->toMediaCollection('avatar');
    $media = $user->profile->getMedia('avatar')[0];
    $path = PathGeneratorFactory::create($media)->getPath($media);
    Storage::disk('public')->assertExists($path);
});

it('upload avatar when file is null', function (): void {
    Storage::fake('public');
    $user = User::factory()->create();
    $user->profile->addMedia(null)->toMediaCollection('avatar');
    $user->profile->getMedia('avatar');
})->throws(TypeError::class);

it('records media conversions', function (): void {
    Storage::fake('public');
    $file = UploadedFile::fake()->image('test-image.jpg', 600, 400);

    $user = User::factory()->create();
    $user->profile->addMedia($file)->toMediaCollection('avatar');
    $media = $user->profile->getMedia('avatar')[0];
    $conversionNames = $media->getGeneratedConversions();

    expect($conversionNames)->toHaveKey('preview')
        ->and($conversionNames['preview'])->toBeTrue();
});

it('returns avatar url when avatar exists', function (): void {
    Storage::fake('public');
    $file = UploadedFile::fake()->image('avatar.jpg');
    $user = User::factory()->create();
    $profile = $user->profile;

    $media = $profile->addMedia($file)->toMediaCollection('avatar');

    expect($profile->avatar)->toBe($media->getUrl());
});

it('returns empty string when avatar does not exist', function (): void {
    $user = User::factory()->create();
    $profile = $user->profile;

    expect($profile->avatar)->toBe('');
});

test('user profile registers avatar media collection', function () {
    $user = User::factory()->create();
    $profile = $user->profile;

    $collections = $profile->getRegisteredMediaCollections();

    expect($collections->contains('name', 'avatar'))->toBeTrue();

    $avatarCollection = $collections->where('name', 'avatar')->first();
    expect($avatarCollection->singleFile)->toBeTrue();
});

test('user profile enforces single file constraint on avatar collection', function () {
    $user = User::factory()->create();
    $profile = $user->profile;

    $profile->addMedia(UploadedFile::fake()->image('avatar1.jpg'))
        ->toMediaCollection('avatar');

    $profile->addMedia(UploadedFile::fake()->image('avatar2.jpg'))
        ->toMediaCollection('avatar');

    expect($profile->getMedia('avatar')->count())->toBe(1);

    expect($profile->getFirstMedia('avatar')->file_name)->toBe('avatar2.jpg');
});

test('avatar attribute returns correct media url', function () {
    $user = User::factory()->create();
    $profile = $user->profile;

    expect($profile->avatar)->toBe('');

    $profile->addMedia(UploadedFile::fake()->image('avatar.jpg'))
        ->toMediaCollection('avatar');

    $profile->refresh();

    expect($profile->avatar)
        ->not->toBeEmpty()
        ->toContain('/avatar.jpg');
});

it('has the correct fillable attributes', function (): void {
    $model = new UserProfile;
    expect($model->getFillable())->toEqual([
        'user_id',
        'name',
        'last_name',
        'title',
        'linkedin',
        'telegram',
        'whatsapp',
        'phone',
        'description',
    ]);
});
