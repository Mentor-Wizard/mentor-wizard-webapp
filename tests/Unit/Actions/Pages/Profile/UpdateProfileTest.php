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

describe('Update Profile', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    it('updates user profile successfully', function (User $user, array $updateData): void {
        Auth::login($user);

        $request = mockUpdateProfileRequest($updateData, $user);
        $action = new UpdateProfilePage;
        $result = $action->handle($request);

        $updatedUser = $user->fresh();

        expect($result)->toBeInstanceOf(RedirectResponse::class)
            ->and($result->getStatusCode())->toBe(Response::HTTP_FOUND)
            ->and($result->getTargetUrl())->toBe(route('profile.edit'))
            ->and($updatedUser->profile->name)->toBe(Arr::get($updateData, 'name'))
            ->and($updatedUser->email)->toBe(Arr::get($updateData, 'email'));
    })->with([
        'updated user with new email' => fn (): array => [
            'user' => User::factory()->withProfile()->create([
                'username'          => 'John',
                'email'             => 'john@example.com',
                'email_verified_at' => now(),
            ]),
            'updateData' => [
                'name'      => 'John',
                'last_name' => 'Dou',
                'email'     => 'john.updated@example.com',
            ],
        ],
        'updated user with same email' => fn (): array => [
            'user' => User::factory()->withProfile()->create([
                'username' => 'Jane',
                'email'    => 'jane@example.com',
            ]),
            'updateData' => [
                'name' => 'Jane',
                'last_name' => 'Dou',
                'email'    => 'jane@example.com',
            ],
        ],
    ]);

    it('resets email verification when email changes', function (): void {
        $user = User::factory()->withProfile()->create([
            'email'    => 'old.email@example.com',
            'email_verified_at' => now(),
        ]);

        Auth::login($user);

        $request = mockUpdateProfileRequest([
            'name'      => 'John',
            'last_name' => 'Dou',
            'email'    => 'new.email@example.com',
        ], $user);

        $action = new UpdateProfilePage;
        $action->handle($request);

        $updatedUser = $user->fresh();

        expect($updatedUser->email_verified_at)->toBeNull();
    });

    it('throws validation exception for invalid data', function ($invalidData): void {
        $user = User::factory()->create();
        Auth::login($user);

        $request = mockUpdateProfileRequest($invalidData, $user);

        $action = new UpdateProfilePage;
        $action->handle($request);
    })->with([
        'empty name'    => ['name' => '', 'email' => 'valid@example.com'],
        'invalid email' => ['name' => 'John', 'email' => 'invalid-email'],
    ])->throws(Error::class);
});

function mockUpdateProfileRequest(array $data, User $user): UpdateProfileRequest|MockInterface
{
    $request = Mockery::mock(UpdateProfileRequest::class);
    $request->shouldReceive('user')->andReturn($user);
    $request->shouldReceive('validated')->andReturn($data);

    $request->shouldReceive('offsetExists')->andReturnUsing(fn($key) => array_key_exists($key, $data));
    $request->shouldReceive('offsetGet')->andReturnUsing(fn($key) => $data[$key] ?? null);
    $request->shouldReceive('hasFile')->with('logo')->andReturn(false);

    return $request;
}
