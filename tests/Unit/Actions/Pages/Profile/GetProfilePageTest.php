<?php

declare(strict_types=1);

use App\Actions\Pages\Profile\GetProfilePage;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Auth;
use Inertia\Response;

mutates(GetProfilePage::class);

describe('Profile Page', function () {
    beforeEach(function () {
        $this->seed(RoleSeeder::class);
    });

    it('returns mustVerifyEmail as true for any user type', function (mixed $user) {
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
            ->and(Arr::get($resultData->getData(), 'page.component'))->toBe('Profile/Edit');
    })->with([
        'verified user' => fn () => User::factory()->create([
            'email_verified_at' => now()->subDay(),
        ]),
        'unverified user' => fn () => User::factory()->create([
            'email_verified_at' => null,
        ]),
        'mocked user' => function () {
            $mock = Mockery::mock(User::class);
            $mock->shouldNotReceive('instanceof')->andReturn(ShouldBeUnique::class);
            $mock->shouldReceive('getAuthIdentifier')->andReturn(1);

            return $mock;
        },
    ]);

    it('returns mustVerifyEmail as true with different session statuses', function (?string $status) {
        $user = User::factory()->create();
        Auth::login($user);

        session(['status' => $status]);

        $action = new GetProfilePage;
        $result = $action->handle();
        $resultData = $result->toResponse(request())->getOriginalContent();

        expect($result)->toBeInstanceOf(Response::class)
            ->and(Arr::get($resultData->getData(), 'page.component'))->toBe('Profile/Edit')
            ->and(Arr::get($resultData->getData(), 'page.props.mustVerifyEmail'))->toBeTrue()
            ->and(Arr::get($resultData->getData(), 'page.props.status'))->toBe($status);
    })->with([
        'no status' => null,
        'with status' => 'test-status',
    ]);
});
