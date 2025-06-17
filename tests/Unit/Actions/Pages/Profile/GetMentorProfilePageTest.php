<?php

declare(strict_types=1);

use App\Actions\Pages\Profile\GetMentorProfilePage;
use App\Enums\RoleEnum;
use App\Enums\RoleGuardEnum;
use App\Models\User;
use App\Models\UserProfile;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Response;
use Spatie\Permission\Models\Role;

mutates(GetMentorProfilePage::class);

describe('Mentor Page', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
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
            'description' => 'profile description',
        ]);
        $user->refresh();
        $user->assignRole(Role::findByName(RoleEnum::MENTOR->value, RoleGuardEnum::MENTOR->value));

        $action = new GetMentorProfilePage;
        $result = $action->handle($user->slug);
        $resultData = $result->toResponse(request())->getOriginalContent();

        expect($result)->toBeInstanceOf(Response::class)
            ->and(Arr::get($resultData->getData(), 'page.component'))->toBe('Profile/Mentor')
            ->and(Arr::get($resultData->getData(), 'page.props.mentor.username'))->toBe('Test User')
            ->and(Arr::get($resultData->getData(), 'page.props.mentor.email'))->toBe('test@example.com')
            ->and(Arr::get($resultData->getData(), 'page.props.mentor.name'))->toBe('profile name')
            ->and(Arr::get($resultData->getData(), 'page.props.mentor.last_name'))->toBe('profile last_name')
            ->and(Arr::get($resultData->getData(), 'page.props.mentor.linkedin'))->toBe('profile linkedin')
            ->and(Arr::get($resultData->getData(), 'page.props.mentor.telegram'))->toBe('profile telegram')
            ->and(Arr::get($resultData->getData(), 'page.props.mentor.whatsapp'))->toBe('profile whatsapp')
            ->and(Arr::get($resultData->getData(), 'page.props.mentor.phone'))->toBe('profile phone')
            ->and(Arr::get($resultData->getData(), 'page.props.mentor.description'))->toBe('profile description')
            ->and(Arr::get($resultData->getData(), 'page.props.reviews'))->toBeArray()
            ->and(Arr::get($resultData->getData(), 'page.props.reviews.total'))->toBe(0)
            ->and(Arr::get($resultData->getData(), 'page.props.defaultAvatar'))->toBe(UserProfile::DEFAULT_AVATAR_URL);
    });

    it('throws AuthorizationException if user is not mentor', function (): void {
        $user = User::factory()->create();

        $action = new GetMentorProfilePage;

        expect(fn (): Response => $action->handle($user->slug))
            ->toThrow(AuthorizationException::class, 'Access denied.');
    });
});
