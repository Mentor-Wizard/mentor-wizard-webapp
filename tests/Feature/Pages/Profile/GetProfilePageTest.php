<?php

declare(strict_types=1);

use App\Actions\Pages\Profile\GetProfilePage;
use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia as Assert;

mutates(GetProfilePage::class);

describe('Profile Page', function (): void {
    it('loads the profile page for an authenticated user', function (): void {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertInertia(fn (Assert $page): AssertableJson => $page
                ->component('Profile/Edit')
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

        $response->assertInertia(fn ($page) => $page->component('Profile/Edit')
            ->has('avatar')
            ->where('avatar', $avatarUrl)
        );
    });
});
