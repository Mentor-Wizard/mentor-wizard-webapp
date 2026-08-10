<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\UserProfile\Actions\AddAvatar;

describe('Add Avatar Action', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        Storage::fake('public');
    });

    it('adds avatar to user profile successfully', function (): void {
        $user = User::factory()->create();
        $avatar = UploadedFile::fake()->image('avatar.jpg', 100, 100);

        $action = new AddAvatar;
        $action->handle($user, $avatar);

        expect($user->profile->getMedia('avatar'))->toHaveCount(1)
            ->and($user->profile->getFirstMediaUrl('avatar'))->not->toBeEmpty();
    });

    it('replaces existing avatar when adding new one', function (): void {
        $user = User::factory()->create();

        // Add first avatar
        $firstAvatar = UploadedFile::fake()->image('first-avatar.jpg', 100, 100);
        $action = new AddAvatar;
        $action->handle($user, $firstAvatar);

        expect($user->profile->getMedia('avatar'))->toHaveCount(1);

        // Add second avatar (should replace the first)
        $secondAvatar = UploadedFile::fake()->image('second-avatar.jpg', 100, 100);
        $action->handle($user, $secondAvatar);

        $user->profile->refresh();
        expect($user->profile->getMedia('avatar'))->toHaveCount(1);
    });

    it('handles non-image files gracefully', function (): void {
        $user = User::factory()->create();
        $textFile = UploadedFile::fake()->create('document.txt', 100);

        $action = new AddAvatar;
        $action->handle($user, $textFile);

        expect($user->profile->getMedia('avatar'))->toHaveCount(1);
    });

    it('handles large image files within reasonable limits', function (): void {
        $user = User::factory()->create();
        // Create a reasonably large file that should be accepted
        $largeFile = UploadedFile::fake()->image('large-avatar.jpg', 800, 600)->size(2000);

        $action = new AddAvatar;
        $action->handle($user, $largeFile);

        expect($user->profile->getMedia('avatar'))->toHaveCount(1);
    });

    it('replaces existing avatar due to singleFile configuration', function (): void {
        $user = User::factory()->create();

        // Add initial avatar
        $firstAvatar = UploadedFile::fake()->image('first-avatar.jpg', 100, 100);
        $user->profile->addMedia($firstAvatar)->toMediaCollection('avatar');

        // Verify we have one avatar
        expect($user->profile->getMedia('avatar'))->toHaveCount(1);
        $firstMediaId = $user->profile->getFirstMedia('avatar')->id;

        // Add a second avatar through our action
        $secondAvatar = UploadedFile::fake()->image('second-avatar.jpg', 200, 200);
        $action = new AddAvatar;
        $action->handle($user, $secondAvatar);

        // Refresh the profile to get the latest state
        $user->profile->refresh();

        // Should still have exactly one avatar due to singleFile() configuration
        expect($user->profile->getMedia('avatar'))->toHaveCount(1);

        // The media ID should be different, proving the old one was replaced
        $currentMediaId = $user->profile->getFirstMedia('avatar')->id;
        expect($currentMediaId)->not->toBe($firstMediaId);
    });
});
