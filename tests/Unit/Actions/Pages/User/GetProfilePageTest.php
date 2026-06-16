<?php

declare(strict_types=1);

use App\Actions\Pages\Profile\GetProfilePage;
use App\Enums\CalendarProviderEnum;
use App\Enums\CalendarSyncStatusEnum;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use App\Models\UserProfile;
use Database\Seeders\RoleSeeder;
use Inertia\Response;

mutates(GetProfilePage::class);

describe('User Page', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    it('returns mustVerifyEmail as true for any user type', function (mixed $user): void {
        if ($user instanceof User) {
            $this->actingAs($user);
        } else {
            Auth::shouldReceive('user')->andReturn($user);
            Auth::shouldReceive('id')->andReturn(1);
        }

        session(['status' => 'test-status']);
        $action = new GetProfilePage;
        $result = $action->handle();
        $resultData = $result->toResponse(request())->getOriginalContent();

        expect($result)->toBeInstanceOf(Response::class)
            ->and(Arr::get($resultData->getData(), 'page.props.mustVerifyEmail'))->toBeTrue()
            ->and(Arr::get($resultData->getData(), 'page.props.status'))->toBe('test-status')
            ->and(Arr::get($resultData->getData(), 'page.component'))->toBe('Profile/EditPage');
    })->with([
        'verified user' => fn () => User::factory()->create([
            'email_verified_at' => now()->subDay(),
        ]),
        'unverified user' => fn () => User::factory()->create([
            'email_verified_at' => null,
        ]),
    ]);

    it('returns mustVerifyEmail as true with different session statuses', function (?string $status): void {
        $user = User::factory()->create();
        $this->actingAs($user);

        session(['status' => $status]);

        $action = new GetProfilePage;
        $result = $action->handle();
        $resultData = $result->toResponse(request())->getOriginalContent();

        expect($result)->toBeInstanceOf(Response::class)
            ->and(Arr::get($resultData->getData(), 'page.component'))->toBe('Profile/EditPage')
            ->and(Arr::get($resultData->getData(), 'page.props.mustVerifyEmail'))->toBeTrue()
            ->and(Arr::get($resultData->getData(), 'page.props.status'))->toBe($status);
    })->with([
        'no status'   => null,
        'with status' => 'test-status',
    ]);

    it('returns calendarIntegrations prop with one entry per CalendarProviderEnum case', function (): void {
        $user = User::factory()->create();
        $this->actingAs($user);

        $action = new GetProfilePage;
        $result = $action->handle();
        $resultData = $result->toResponse(request())->getOriginalContent();

        $integrations = Arr::get($resultData->getData(), 'page.props.calendarIntegrations');

        expect($integrations)->toBeArray()
            ->toHaveSameSize(CalendarProviderEnum::cases());
    });

    it('returns all required keys for each calendar integration entry', function (): void {
        $user = User::factory()->create();
        $this->actingAs($user);

        $action = new GetProfilePage;
        $result = $action->handle();
        $resultData = $result->toResponse(request())->getOriginalContent();

        $integrations = Arr::get($resultData->getData(), 'page.props.calendarIntegrations');

        expect($integrations)->each->toHaveKeys([
            'key',
            'connected',
            'needs_reauth',
            'sync_status',
            'calendar_id',
            'calendar_name',
            'last_synced_at',
            'last_error_message',
            'uses_app_credentials',
            'is_cal_dav',
        ]);
    });

    it('marks a connected integration as connected=true with its sync_status', function (): void {
        $user = User::factory()->create();
        $this->actingAs($user);

        UserCalendarIntegration::factory()->create([
            'user_id'      => $user->getKey(),
            'provider'     => CalendarProviderEnum::GOOGLE,
            'sync_status'  => CalendarSyncStatusEnum::ACTIVE,
            'needs_reauth' => false,
        ]);

        $action = new GetProfilePage;
        $result = $action->handle();
        $resultData = $result->toResponse(request())->getOriginalContent();

        $integrations = Arr::get($resultData->getData(), 'page.props.calendarIntegrations');
        $googleIntegration = collect($integrations)->firstWhere('key', CalendarProviderEnum::GOOGLE->value);

        expect($googleIntegration['connected'])->toBeTrue()
            ->and($googleIntegration['sync_status'])->toBe(CalendarSyncStatusEnum::ACTIVE->value)
            ->and($googleIntegration['needs_reauth'])->toBeFalse();
    });

    it('marks a missing integration as connected=false with disconnected sync_status', function (): void {
        $user = User::factory()->create();
        $this->actingAs($user);

        $action = new GetProfilePage;
        $result = $action->handle();
        $resultData = $result->toResponse(request())->getOriginalContent();

        $integrations = Arr::get($resultData->getData(), 'page.props.calendarIntegrations');
        $outlookIntegration = collect($integrations)->firstWhere('key', CalendarProviderEnum::OUTLOOK->value);

        expect($outlookIntegration['connected'])->toBeFalse()
            ->and($outlookIntegration['sync_status'])->toBe(CalendarSyncStatusEnum::DISCONNECTED->value);
    });

    it('returns the avatar prop with default url when no avatar is set', function (): void {
        $user = User::factory()->create();
        $this->actingAs($user);

        $action = new GetProfilePage;
        $result = $action->handle();
        $resultData = $result->toResponse(request())->getOriginalContent();

        expect(Arr::get($resultData->getData(), 'page.props.avatar'))->toBe(UserProfile::DEFAULT_AVATAR_URL);
    });

    it('returns the default avatar url when user has no media avatar set', function (): void {
        $user = User::factory()->create();
        $this->actingAs($user);

        $action = new GetProfilePage;
        $result = $action->handle();
        $resultData = $result->toResponse(request())->getOriginalContent();

        expect(Arr::get($resultData->getData(), 'page.props.avatar'))->toBe(UserProfile::DEFAULT_AVATAR_URL);
    });

    it('uses integration data from DB not null when provider has a matching integration', function (): void {
        $user = User::factory()->create();
        $this->actingAs($user);

        UserCalendarIntegration::factory()->create([
            'user_id'       => $user->getKey(),
            'provider'      => CalendarProviderEnum::GOOGLE,
            'calendar_id'   => 'my-cal-id',
            'calendar_name' => 'My Calendar',
        ]);

        $action = new GetProfilePage;
        $result = $action->handle();
        $resultData = $result->toResponse(request())->getOriginalContent();

        $integrations = Arr::get($resultData->getData(), 'page.props.calendarIntegrations');
        $googleIntegration = collect($integrations)->firstWhere('key', CalendarProviderEnum::GOOGLE->value);

        expect($googleIntegration['calendar_id'])->toBe('my-cal-id')
            ->and($googleIntegration['calendar_name'])->toBe('My Calendar');
    });
});
