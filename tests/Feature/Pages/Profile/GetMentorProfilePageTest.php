<?php

declare(strict_types=1);

use App\Actions\Pages\Profile\GetMentorProfilePage;
use App\Enums\RoleEnum;
use App\Enums\RoleGuardEnum;
use App\Models\MentorReview;
use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\RoleSeeder;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

mutates(GetMentorProfilePage::class);

describe('Mentor Profile Page', function (): void {
    it('loads the mentor profile page', function (): void {
        $this->seed(RoleSeeder::class);
        $mentor = User::factory()->create([
            'username' => 'Mentor User',
            'email'    => 'mentor@example.com',
        ]);
        $mentor->profile->update([
            'name'        => 'Mentor profile name',
            'last_name'   => 'Mentor profile last_name',
            'linkedin'    => 'Mentor profile linkedin',
            'telegram'    => 'Mentor profile telegram',
            'whatsapp'    => 'Mentor profile whatsapp',
            'phone'       => 'Mentor profile phone',
            'description' => 'Mentor profile description',
        ]);
        $mentor->refresh();
        $mentor->assignRole(Role::findByName(RoleEnum::MENTOR->value, RoleGuardEnum::MENTOR->value));

        $menti = User::factory()->create([
            'username' => 'Menti User',
            'email'    => 'menti@example.com',
        ]);
        $menti->profile->update([
            'name'        => 'Menti profile name',
            'last_name'   => 'Menti profile last_name',
            'linkedin'    => 'Menti profile linkedin',
            'telegram'    => 'Menti profile telegram',
            'whatsapp'    => 'Menti profile whatsapp',
            'phone'       => 'Menti profile phone',
            'description' => 'Menti profile description',
        ]);
        $menti->refresh();
        $menti->assignRole(Role::findByName(RoleEnum::MENTI->value, RoleGuardEnum::MENTI->value));

        MentorReview::factory()->create([
            'mentor_id' => $mentor->id,
            'menti_id'  => $menti->id,
            'comment'   => 'Perfect',
            'rating'    => 5,
        ]);

        $this->get(route('page.mentor', ['slug' => $mentor->slug]))
            ->assertInertia(fn (Assert $page): AssertableJson => $page
                ->component('Profile/Mentor')
                ->has('mentor', fn (Assert $mentorData): AssertableJson => $mentorData
                    ->where('username', 'Mentor User')
                    ->where('email', 'mentor@example.com')
                    ->where('rating', 5)
                    ->has('profile', fn (Assert $profile): AssertableJson => $profile
                        ->where('name', 'Mentor profile name')
                        ->where('last_name', 'Mentor profile last_name')
                        ->where('linkedin', 'Mentor profile linkedin')
                        ->where('telegram', 'Mentor profile telegram')
                        ->where('whatsapp', 'Mentor profile whatsapp')
                        ->where('phone', 'Mentor profile phone')
                        ->where('description', 'Mentor profile description')
                        ->where('avatar', '')
                        ->etc()
                    )
                    ->etc()
                )
                ->has('reviews.data', 1)
                ->has('reviews.data.0', fn (Assert $review): AssertableJson => $review
                    ->where('mentor_id', $mentor->id)
                    ->where('menti_id', $menti->id)
                    ->where('comment', 'Perfect')
                    ->where('rating', 5)
                    ->has('menti', fn (Assert $mentiData): AssertableJson => $mentiData
                        ->where('id', $menti->id)
                        ->where('username', 'Menti User')
                        ->where('email', 'menti@example.com')
                        ->has('profile', fn (Assert $profile): AssertableJson => $profile
                            ->where('name', 'Menti profile name')
                            ->where('last_name', 'Menti profile last_name')
                            ->where('linkedin', 'Menti profile linkedin')
                            ->where('telegram', 'Menti profile telegram')
                            ->where('whatsapp', 'Menti profile whatsapp')
                            ->where('phone', 'Menti profile phone')
                            ->where('description', 'Menti profile description')
                            ->etc()
                        )
                        ->etc()
                    )
                    ->etc()
                )
                ->where('defaultAvatar', UserProfile::DEFAULT_AVATAR_URL)
            );
    });

    it('loads the mentor profile page with wrong slug', function (): void {
        $this->get(route('page.mentor', ['slug' => 'random-slug']))
            ->assertNotFound();
    });
});
