<?php

declare(strict_types=1);

use App\Enums\RoleEnum;
use App\Http\Resources\SimilarMentorResource;
use App\Models\Currency;
use App\Models\MentorProfile;
use App\Models\MentorReview;
use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;

covers(SimilarMentorResource::class);

describe('Similar Mentor Resource', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->seed(CurrencySeeder::class);
    });

    it('correctly transforms user resource', function (): void {
        $user = User::factory()->create([
            'username' => 'Test User',
            'email'    => 'test@example.com',
        ]);
        $user->profile->update([
            'name'        => 'profile name',
            'last_name'   => 'profile last_name',
            'linkedin'    => 'profile linkedin',
            'telegram'    => 'profile telegram',
            'whatsapp'    => 'profile whatsapp',
            'phone'       => 'profile phone',
        ]);
        $user->refresh();
        $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $currency = Currency::query()->first();
        MentorProfile::factory()->create([
            'user_id'                  => $user->id,
            'title'                    => 'title',
            'description'              => 'description',
            'rate'                     => 1.1,
            'currency_id'              => $currency->id,
            'experience_started_at'    => Date::now()->subYears(5)->subMonths(6)->format('Y-m-d'),
        ]);

        MentorReview::factory()->create([
            'mentor_id' => $user->id,
            'rating'    => 1,
        ]);
        MentorReview::factory()->create([
            'mentor_id' => $user->id,
            'rating'    => 2,
        ]);
        MentorReview::factory()->create([
            'mentor_id' => $user->id,
            'rating'    => 2,
        ]);

        $resource = SimilarMentorResource::make($user)->resolve();

        expect($resource)->toMatchArray([
            'id'           => $user->id,
            'name'         => 'profile name profile last_name',
            'avatar'       => UserProfile::DEFAULT_AVATAR_URL,
            'title'        => 'title',
            'rate'         => '1.10',
            'currency'     => $currency->symbol,
            'rating'       => 1.7,
            'reviews'      => 3,
            'slug'         => $user->slug,
        ]);
    });

    it('correctly transforms user resource with defaukt value', function (): void {
        $user = User::factory()->create([
            'username' => 'Test User',
            'email'    => 'test@example.com',
        ]);

        $user->refresh();
        $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $currency = Currency::query()->first();

        $resource = SimilarMentorResource::make($user)->resolve();

        expect($resource)->toMatchArray([
            'id'           => $user->id,
            'name'         => '',
            'avatar'       => UserProfile::DEFAULT_AVATAR_URL,
            'title'        => null,
            'rate'         => null,
            'currency'     => null,
            'rating'       => 0,
            'reviews'      => 0,
            'slug'         => $user->slug,
        ]);
    });

    it('loads required relationships', function (): void {
        $user = User::factory()->create();
        $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $freshUser = User::query()->whereKey($user->id)->firstOrFail();

        $mock = Mockery::mock($freshUser)->makePartial();
        $mock->shouldAllowMockingProtectedMethods();
        $mock->shouldReceive('load')
            ->once()
            ->with('mentorProfile.currency')
            ->andReturn($mock);

        $mock->setRelation('mentorProfile', $freshUser->mentorProfile);
        $mock->id = $freshUser->id;
        $mock->slug = $freshUser->slug;

        SimilarMentorResource::make($mock)->resolve();
    });
});
