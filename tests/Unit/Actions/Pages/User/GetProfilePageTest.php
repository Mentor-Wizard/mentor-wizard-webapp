<?php

declare(strict_types=1);

use App\Actions\Pages\Profile\GetProfilePage;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Auth;
use Inertia\Response;

mutates(GetProfilePage::class);

describe('User Page', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    it('returns mustVerifyEmail as true for any user type', function (mixed $user): void {
        if ($user instanceof User) {
            Auth::login($user);
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
        Auth::login($user);

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
});
