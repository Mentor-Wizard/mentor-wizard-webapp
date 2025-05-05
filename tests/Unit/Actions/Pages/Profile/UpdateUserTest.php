<?php

declare(strict_types=1);

use App\Actions\Pages\Profile\UpdateUserPage;
use App\Http\Requests\Profile\UpdateUserRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Response;

mutates(UpdateUserPage::class);

describe('Update Main Profile', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    it('updates user main profile successfully', function (User $user, array $updateData): void {
        Auth::login($user);

        $request = mockUpdateUserRequest($updateData, $user);
        $action = new UpdateUserPage;
        $result = $action->handle($request);

        $updatedUser = $user->fresh();

        expect($result)->toBeInstanceOf(RedirectResponse::class)
            ->and($result->getStatusCode())->toBe(Response::HTTP_FOUND)
            ->and($result->getTargetUrl())->toBe(route('profile.edit'))
            ->and($updatedUser->username)->toBe(Arr::get($updateData, 'username'))
            ->and($updatedUser->email)->toBe(Arr::get($updateData, 'email'));
    })->with([
        'main updated user with new email' => fn (): array => [
            'user' => User::factory()->create([
                'username'          => 'John',
                'email'             => 'john@example.com',
                'email_verified_at' => now(),
            ]),
            'updateData' => [
                'username'        => 'John updated',
                'email'           => 'john.updated@example.com',
            ],
        ],
        'main updated user with same email' => fn (): array => [
            'user' => User::factory()->create([
                'username' => 'Jane',
                'email'    => 'jane@example.com',
            ]),
            'updateData' => [
                'username'        => 'Jane',
                'email'           => 'jane@example.com',
            ],
        ],
    ]);

    it('resets email verification when email changes', function (): void {
        $user = User::factory()->create([
            'email'             => 'old.email@example.com',
            'email_verified_at' => now(),
        ]);

        Auth::login($user);

        $request = mockUpdateUserRequest([
            'username'        => 'John',
            'email'           => 'new.email@example.com',
        ], $user);

        $action = new UpdateUserPage;
        $action->handle($request);

        $updatedUser = $user->fresh();

        expect($updatedUser->email_verified_at)->toBeNull();
    });

    it('throws validation exception for invalid data', function ($invalidData): void {
        $user = User::factory()->create();
        Auth::login($user);

        $request = mockUpdateUserRequest($invalidData, $user);

        $action = new UpdateUserPage;
        $action->handle($request);
    })->with([
        'empty name'    => ['username' => '', 'email' => 'valid@example.com'],
        'invalid email' => ['username' => 'John', 'email' => 'invalid-email'],
    ])->throws(Error::class);
});

function mockUpdateUserRequest(array $data, User $user): UpdateUserRequest|MockInterface
{
    $request = Mockery::mock(UpdateUserRequest::class);
    $request->shouldReceive('user')->andReturn($user);
    $request->shouldReceive('validated')->andReturn($data);
    $request->shouldReceive('get')->with('email')->andReturn($data['email']);
    $request->shouldReceive('get')->with('username')->andReturn($data['username']);
    $request->shouldReceive('hasFile')->with('avatar')->andReturn(false);

    return $request;
}
