<?php

declare(strict_types=1);

use App\Actions\Pages\Profile\UpdateProfileMainPage;
use App\Http\Requests\Profile\UpdateProfileMainRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Response;

mutates(UpdateProfileMainPage::class);

describe('Update Main Profile', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    it('updates user main profile successfully', function (User $user, array $updateData): void {
        Auth::login($user);

        $request = mockUpdateMainProfileRequest($updateData, $user);
        $action = new UpdateProfileMainPage;
        $result = $action->handle($request);

        $updatedUser = $user->fresh();

        expect($result)->toBeInstanceOf(RedirectResponse::class)
            ->and($result->getStatusCode())->toBe(Response::HTTP_FOUND)
            ->and($result->getTargetUrl())->toBe(route('profile.edit'))
            ->and($updatedUser->profile->name)->toBe(Arr::get($updateData, 'name'))
            ->and($updatedUser->email)->toBe(Arr::get($updateData, 'email'));
    })->with([
        'main updated user with new email' => fn (): array => [
            'user' => User::factory()->withProfile()->create([
                'username'          => 'John',
                'email'             => 'john@example.com',
                'email_verified_at' => now(),
            ]),
            'updateData' => [
                'name'        => 'John',
                'last_name'   => 'Dou',
                'email'       => 'john.updated@example.com',
            ],
        ],
        'main updated user with same email' => fn (): array => [
            'user' => User::factory()->withProfile()->create([
                'username' => 'Jane',
                'email'    => 'jane@example.com',
            ]),
            'updateData' => [
                'name'        => 'Jane',
                'last_name'   => 'Dou',
                'email'       => 'jane@example.com',
            ],
        ],
    ]);


    it('check data update', function (): void {
        $user = User::factory()->withProfile()->create();
        $updateData = [
            'name'        => 'Jane',
            'last_name'   => 'Dou',
            'email'       => 'jane@example.com',
        ];

        $action = new UpdateProfileMainPage;
        $reflection = new ReflectionClass(UpdateProfileMainPage::class);
        $method = $reflection->getMethod('dataUpdate');
        $method->setAccessible(true);

        $request = mockUpdateMainProfileRequest($updateData, $user);
        $data = $method->invoke($action, $request);
        expect($data['name'])->toBe('Jane')
            ->and($data['last_name'])->toBe('Dou');
    });

    it('resets email verification when email changes', function (): void {
        $user = User::factory()->withProfile()->create([
            'email'             => 'old.email@example.com',
            'email_verified_at' => now(),
        ]);

        Auth::login($user);

        $request = mockUpdateMainProfileRequest([
            'name'        => 'John',
            'last_name'   => 'Dou',
            'email'       => 'new.email@example.com',
        ], $user);

        $action = new UpdateProfileMainPage;
        $action->handle($request);

        $updatedUser = $user->fresh();

        expect($updatedUser->email_verified_at)->toBeNull();
    });

    it('throws validation exception for invalid data', function ($invalidData): void {
        $user = User::factory()->create();
        Auth::login($user);

        $request = mockUpdateMainProfileRequest($invalidData, $user);

        $action = new UpdateProfileMainPage;
        $action->handle($request);
    })->with([
        'empty name'    => ['name' => '', 'email' => 'valid@example.com'],
        'invalid email' => ['name' => 'John', 'email' => 'invalid-email'],
    ])->throws(Error::class);
});

function mockUpdateMainProfileRequest(array $data, User $user): UpdateProfileMainRequest|MockInterface
{
    $request = Mockery::mock(UpdateProfileMainRequest::class);
    $request->shouldReceive('user')->andReturn($user);
    $request->shouldReceive('validated')->andReturn($data);
    $request->shouldReceive('get')->with('email')->andReturn($data['email']);
    $request->shouldReceive('get')->with('name')->andReturn($data['name']);
    $request->shouldReceive('get')->with('last_name')->andReturn($data['last_name']);
    $request->shouldReceive('hasFile')->with('avatar')->andReturn(false);

    return $request;
}

