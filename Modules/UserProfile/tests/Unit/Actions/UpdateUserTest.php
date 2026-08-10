<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Modules\UserProfile\Actions\UpdateUser;
use Modules\UserProfile\Http\Requests\UpdateUserRequest;
use Symfony\Component\HttpFoundation\Response;

mutates(UpdateUser::class);

describe('Update Main User', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        Storage::fake('public');
    });

    it('updates user main profile successfully', function (User $user, array $updateData): void {
        Auth::login($user);

        $request = mockUpdateUserRequest($updateData, $user);
        $action = new UpdateUser;
        $result = $action->handle($request);

        $updatedUser = $user->fresh();

        expect($result)->toBeInstanceOf(RedirectResponse::class)
            ->and($result->getStatusCode())->toBe(Response::HTTP_FOUND)
            ->and($result->getTargetUrl())->toBe(route('profile.edit'))
            ->and($updatedUser->username)->toBe(Arr::get($updateData, 'username'))
            ->and($updatedUser->email)->toBe(Arr::get($updateData, 'email'));
    })->with([
        'main updated user with new email'  => fn (): array => [
            'user'       => User::factory()->create([
                'username'          => 'John',
                'email'             => 'john@example.com',
                'email_verified_at' => now(),
            ]),
            'updateData' => [
                'username' => 'John updated',
                'email'    => 'john.updated@example.com',
            ],
        ],
        'main updated user with same email' => fn (): array => [
            'user'       => User::factory()->create([
                'username' => 'Jane',
                'email'    => 'jane@example.com',
            ]),
            'updateData' => [
                'username' => 'Jane',
                'email'    => 'jane@example.com',
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
            'username' => 'John',
            'email'    => 'new.email@example.com',
        ], $user);

        $action = new UpdateUser;
        $action->handle($request);

        $updatedUser = $user->fresh();

        expect($updatedUser->email_verified_at)->toBeNull();
    });

    it('updates user profile with avatar successfully', function (): void {
        $user = User::factory()->create([
            'username' => 'John',
            'email'    => 'john@example.com',
        ]);

        Auth::login($user);

        $avatar = UploadedFile::fake()->image('avatar.jpg', 100, 100);
        $updateData = [
            'username' => 'John Updated',
            'email'    => 'john.updated@example.com',
        ];

        $request = mockUpdateUserRequestWithAvatar($updateData, $user, $avatar);
        $action = new UpdateUser;
        $result = $action->handle($request);

        $updatedUser = $user->fresh();

        expect($result)->toBeInstanceOf(RedirectResponse::class)
            ->and($result->getTargetUrl())->toBe(route('profile.edit'))
            ->and($updatedUser->username)->toBe('John Updated')
            ->and($updatedUser->email)->toBe('john.updated@example.com')
            ->and($updatedUser->profile->getMedia('avatar'))->toHaveCount(1);
    });

    it('throws validation exception for invalid data', function (array $invalidData): void {
        $user = User::factory()->create();
        Auth::login($user);

        $request = mockUpdateUserRequest($invalidData, $user);

        $action = new UpdateUser;
        $action->handle($request);
    })->with([
        'empty name'    => ['username' => '', 'email' => 'valid@example.com'],
        'invalid email' => ['username' => 'John', 'email' => 'invalid-email'],
    ])->throws(Error::class);
});

/**
 * @param  array<string, mixed>  $data
 */
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

/**
 * @param  array<string, mixed>  $data
 */
function mockUpdateUserRequestWithAvatar(array $data, User $user, UploadedFile $avatar): UpdateUserRequest|MockInterface
{
    $request = Mockery::mock(UpdateUserRequest::class);
    $request->shouldReceive('user')->andReturn($user);
    $request->shouldReceive('validated')->andReturn($data);
    $request->shouldReceive('get')->with('email')->andReturn($data['email']);
    $request->shouldReceive('get')->with('username')->andReturn($data['username']);
    $request->shouldReceive('hasFile')->with('avatar')->andReturn(true);
    $request->shouldReceive('file')->with('avatar')->andReturn($avatar);

    return $request;
}
