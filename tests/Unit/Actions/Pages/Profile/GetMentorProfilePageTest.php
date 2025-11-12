<?php

declare(strict_types=1);

use App\Actions\Pages\Profile\GetMentorProfilePage;
use App\Enums\RoleEnum;
use App\Models\Currency;
use App\Models\MentorProfile;
use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Inertia\Response;
use Spatie\Permission\Models\Role;

mutates(GetMentorProfilePage::class);

describe('Mentor Page', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->seed(CurrencySeeder::class);
    });

    it('Mentor`s data correct', function (): void {
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
        $currencies = Currency::query()->pluck('name', 'id')->toArray();
        $currency_id = array_key_first($currencies);
        MentorProfile::factory()->create([
            'user_id'                  => $user->id,
            'title'                    => 'title',
            'description'              => 'description',
            'rate'                     => 1.1,
            'currency_id'              => $currency_id,
            'experience_started_at'    => Date::now()->subYears(5)->subMonths(6)->format('Y-m-d'),
        ]);

        $user->refresh();
        $user->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $action = new GetMentorProfilePage;
        $result = $action->handle($user);
        $resultData = $result->toResponse(request())->getOriginalContent();

        expect($result)->toBeInstanceOf(Response::class)
            ->and(Arr::get($resultData->getData(), 'page.component'))->toBe('Profile/Mentor/ViewPage')
            ->and(Arr::get($resultData->getData(), 'page.props.mentor.slug'))->toBe('test-user')
            ->and(Arr::get($resultData->getData(), 'page.props.mentor.titleBlock.name'))->toBe('profile name profile last_name')
            ->and(Arr::get($resultData->getData(), 'page.props.mentor.titleBlock.avatar'))->toBe(UserProfile::DEFAULT_AVATAR_URL)
            ->and(Arr::get($resultData->getData(), 'page.props.mentor.titleBlock.title'))->toBe('title')
            ->and(Arr::get($resultData->getData(), 'page.props.mentor.titleBlock.description'))->toBe('description')
            ->and(Arr::get($resultData->getData(), 'page.props.mentor.titleBlock.rate'))->toBe('1.10')
            ->and(Arr::get($resultData->getData(), 'page.props.mentor.titleBlock.experience'))->toBe('5 years')
            ->and(Arr::get($resultData->getData(), 'page.props.mentor.statisticBlock.star_5'))->toBe(0)
            ->and(Arr::get($resultData->getData(), 'page.props.mentor.statisticBlock.star_4'))->toBe(0)
            ->and(Arr::get($resultData->getData(), 'page.props.mentor.statisticBlock.star_3'))->toBe(0)
            ->and(Arr::get($resultData->getData(), 'page.props.mentor.statisticBlock.star_2'))->toBe(0)
            ->and(Arr::get($resultData->getData(), 'page.props.mentor.statisticBlock.star_1'))->toBe(0);
    });

    it('throws AuthorizationException if user is not mentor', function (): void {
        $user = User::factory()->create();

        $action = new GetMentorProfilePage;

        expect(fn (): Response => $action->handle($user))
            ->toThrow(ModelNotFoundException::class);
    });
});
