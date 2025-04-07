<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\Support\PathGenerator\PathGeneratorFactory;

covers(UserProfile::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('can create a user profile', function () {
    $user = User::factory()->create();

    $profile = UserProfile::factory()->create(['user_id' => $user->id]);

    expect($profile)->toBeInstanceOf(UserProfile::class)
        ->and($profile->user_id)->toBe($user->id);
});

it('belongs to the user', function () {
    $user = User::factory()->create();
    $profile = UserProfile::factory()->create(['user_id' => $user->id]);

    expect($profile->user)->toBeInstanceOf(User::class)
        ->and($profile->user->id)->toBe($user->id);
});

it('successfully upload an image', function () {
    Storage::fake('public');
    $file = UploadedFile::fake()->image('test-image.jpg', 600, 400);

    $user = User::factory()->withProfile()->create();
    $user->profile->addMedia($file)->toMediaCollection('avatar');
    $media = $user->profile->getMedia('avatar')[0];
    $path = PathGeneratorFactory::create($media)->getPath($media);
    Storage::disk('public')->assertExists($path);
});

it('upload avatar when profile is null', function () {
    $this->expectExceptionMessage('Call to a member function getMedia() on null');
    $user = User::factory()->create();
    $user->profile->getMedia('avatar')[0];
})->throws(Error::class);

it('upload avatar when file is null', function () {
    Storage::fake('public');
    $user = User::factory()->withProfile()->create();
    $user->profile->addMedia(null)->toMediaCollection('avatar');
    $user->profile->getMedia('avatar')[0];
})->throws(TypeError::class);

it('records media conversions', function () {
    Storage::fake('public');
    $file = UploadedFile::fake()->image('test-image.jpg', 600, 400);

    $user = User::factory()->withProfile()->create();
    $user->profile->addMedia($file)->toMediaCollection('avatar');
    $media = $user->profile->getMedia('avatar')[0];
    $conversionNames = $media->getGeneratedConversions();

    expect($conversionNames)->toHaveKey('preview')
        ->and($conversionNames['preview'])->toBeTrue();
});
