<?php

declare(strict_types=1);

use App\Actions\Pages\Profile\UpdateProfilePage;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Response;

mutates(UpdateProfilePage::class);

describe('Update Profile', function () {
    beforeEach(function () {
        $this->seed(RoleSeeder::class);
    });

    it('updates user profile successfully', function (User $user, array $updateData) {
        Auth::login($user);

        $request = mockUpdateProfileRequest($updateData, $user);
        $action = new UpdateProfilePage;
        $result = $action->handle($request);

        $updatedUser = $user->fresh();

        expect($result)->toBeInstanceOf(RedirectResponse::class)
            ->and($result->getStatusCode())->toBe(Response::HTTP_FOUND)
            ->and($result->getTargetUrl())->toBe(route('profile.edit'))
            ->and($updatedUser->username)->toBe(Arr::get($updateData, 'username'))
            ->and($updatedUser->email)->toBe(Arr::get($updateData, 'email'));
    })->with([
        'updated user with new email' => function () {
            return [
                'user' => User::factory()->create([
                    'username' => 'John',
                    'email' => 'john@example.com',
                    'email_verified_at' => now(),
                ]),
                'updateData' => [
                    'username' => 'John',
                    'email' => 'john.updated@example.com',
                ],
            ];
        },
        'updated user with same email' => function () {
            return [
                'user' => User::factory()->create([
                    'username' => 'Jane',
                    'email' => 'jane@example.com',
                ]),
                'updateData' => [
                    'username' => 'Jane',
                    'email' => 'jane@example.com',
                ],
            ];
        },
    ]);

    it('resets email verification when email changes', function () {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Auth::login($user);

        $request = mockUpdateProfileRequest([
            'username' => $user->username,
            'email' => 'new.email@example.com',
        ], $user);

        $action = new UpdateProfilePage;
        $action->handle($request);

        $updatedUser = $user->fresh();

        expect($updatedUser->email_verified_at)->toBeNull();
    });

    it('throws validation exception for invalid data', function ($invalidData) {
        $user = User::factory()->create();
        Auth::login($user);

        $request = mockUpdateProfileRequest($invalidData, $user);

        $action = new UpdateProfilePage;
        $action->handle($request);
    })->with([
        'empty name' => ['username' => '', 'email' => 'valid@example.com'],
        'invalid email' => ['username' => 'John', 'email' => 'invalid-email'],
    ])->throws(Error::class);
});

function mockUpdateProfileRequest(array $data, User $user): UpdateProfileRequest|MockInterface
{
    $request = Mockery::mock(UpdateProfileRequest::class);
    $request->shouldReceive('user')->andReturn($user);
    $request->shouldReceive('validated')->andReturn($data);

    return $request;
}
