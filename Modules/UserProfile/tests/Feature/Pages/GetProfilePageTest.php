<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\UserProfile\Actions\Pages\GetProfilePage;
use Modules\UserProfile\Models\UserProfile;

mutates(GetProfilePage::class);

describe('User Page', function (): void {
    it('loads the profile page for an authenticated user', function (): void {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertInertia(fn (Assert $page): AssertableJson => $page
                ->component('UserProfile/EditPage')
                ->has('mustVerifyEmail')
                ->where('status', null)
                ->has('avatar')
                ->where('avatar', UserProfile::DEFAULT_AVATAR_URL)
            );
    });

    it('does not allow an unauthenticated user to access the profile page', function (): void {
        $this->get(route('profile.edit'))
            ->assertRedirect(route('login'));
    });

    it('shows uploaded avatar if media exists', function (): void {
        Storage::fake('public');
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $this->actingAs($user);

        $file = UploadedFile::fake()->image('avatar.jpg');
        $user->profile->addMedia($file)->toMediaCollection('avatar');

        $avatarUrl = $user->profile->getFirstMediaUrl('avatar');

        $response = $this->get(route('profile.edit'));

        $response->assertInertia(fn ($page) => $page->component('UserProfile/EditPage')
            ->has('avatar')
            ->where('avatar', $avatarUrl)
        );
    });
});
