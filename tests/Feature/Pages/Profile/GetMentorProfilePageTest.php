<?php

declare(strict_types=1);

use App\Actions\Pages\Profile\GetMentorProfilePage;
use App\Enums\RoleEnum;
use App\Models\Currency;
use App\Models\MentorProfile;
use App\Models\MentorReview;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\Carbon;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\RoleSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

mutates(GetMentorProfilePage::class);

describe('Mentor Profile Page', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->seed(CurrencySeeder::class);
    });

    it('loads the mentor profile page', function (): void {
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
        ]);
        $mentor->refresh();
        $mentor->assignRole(Role::findByName(RoleEnum::MENTOR->value));

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
        ]);
        $menti->refresh();
        $menti->assignRole(Role::findByName(RoleEnum::MENTI->value));

        MentorReview::factory()->create([
            'mentor_id' => $mentor->id,
            'menti_id'  => $menti->id,
            'comment'   => 'Perfect',
            'rating'    => 5,
        ]);

        $currencies = Currency::query()->pluck('name', 'id')->toArray();
        $currency_id = array_key_first($currencies);
        MentorProfile::factory()->create([
            'user_id'                  => $mentor->id,
            'title'                    => 'title',
            'description'              => 'description',
            'rate'                     => 1.1,
            'currency_id'              => $currency_id,
            'experience_started_at'    => Carbon::now()->subYears(5)->subMonths(6)->format('Y-m-d'),
        ]);

        $this->get(route('page.mentor', ['user' => $mentor->slug]))
            ->assertInertia(fn (Assert $page): \Inertia\Testing\AssertableInertia => $page
                ->component('Profile/Mentor/View')
                ->where('mentor.titleBlock.name', 'Mentor profile name Mentor profile last_name')
                ->where('mentor.titleBlock.avatar', UserProfile::DEFAULT_AVATAR_URL)
                ->where('mentor.titleBlock.title', 'title')
                ->where('mentor.titleBlock.description', 'description')
                ->where('mentor.titleBlock.rate', '1.10')
                ->where('mentor.titleBlock.experience', '5 years')
                ->where('mentor.statisticBlock.star_5', 1)
                ->where('mentor.statisticBlock.star_4', 0)
                ->where('mentor.statisticBlock.star_3', 0)
                ->where('mentor.statisticBlock.star_2', 0)
                ->where('mentor.statisticBlock.star_1', 0)
                ->etc()
            );
    });

    it('loads the mentor profile page with wrong slug', function (): void {
        $this->get(route('page.mentor', ['user' => 'random-slug']))
            ->assertNotFound();
    });
});
