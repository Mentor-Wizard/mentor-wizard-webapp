<?php

declare(strict_types=1);

use App\Actions\Profile\UpdateUserProfile;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Requests\UserProfile\UpdateUserProfileRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Mockery\MockInterface;
use Symfony\Component\HttpFoundation\Response;

mutates(UpdateUserProfile::class);

describe('Update Info User', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
    });

    it('updates user info profile successfully', function (User $user, array $updateData): void {
        Auth::login($user);

        $request = mockUpdateProfileRequest($updateData, $user);
        $action = new UpdateUserProfile;
        $result = $action->handle($request);

        expect($result)->toBeInstanceOf(RedirectResponse::class)
            ->and($result->getStatusCode())->toBe(Response::HTTP_FOUND)
            ->and($result->getTargetUrl())->toBe(route('profile.edit'));

        $user->refresh();

        expect($user->profile->linkedin)->toBe('https://www.linkedin.com/in/john')
            ->and($user->profile->name)->toBe('John')
            ->and($user->profile->last_name)->toBe('Dou')
            ->and($user->profile->telegram)->toBe('https://t.me/john')
            ->and($user->profile->whatsapp)->toBe('https://wa.me/john')
            ->and($user->profile->description)->toBe('description')
            ->and($user->profile->phone)->toBe('+380671234567');
    })->with([
        'info updated user with new email' => fn (): array => [
            'user'       => User::factory()->create(),
            'updateData' => [
                'name'        => 'John',
                'last_name'   => 'Dou',
                'linkedin'    => 'https://www.linkedin.com/in/john',
                'telegram'    => 'https://t.me/john',
                'whatsapp'    => 'https://wa.me/john',
                'description' => 'description',
                'phone'       => '+380671234567',
            ],
        ],
    ]);
});

function mockUpdateProfileRequest(array $data, User $user): UpdateUserRequest|MockInterface
{
    $request = Mockery::mock(UpdateUserProfileRequest::class);
    $request->shouldReceive('validated')->andReturn($data);

    return $request;
}
