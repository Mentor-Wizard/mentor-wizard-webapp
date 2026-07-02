<?php

declare(strict_types=1);

use App\Actions\Profile\DeleteUserProfile;
use App\Http\Requests\UserProfile\DeleteUserProfileRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\Concerns\AsController;

describe('DeleteUserProfile', function (): void {
    it('logs out the user', function (): void {
        Auth::shouldReceive('logout')->once();

        $user = Mockery::mock(User::class);
        $user->shouldReceive('delete')->once();

        $request = Mockery::mock(DeleteUserProfileRequest::class);
        $request->shouldReceive('user')->once()->andReturn($user);
        $request->shouldReceive('session->invalidate')->once();
        $request->shouldReceive('session->regenerateToken')->once();

        $action = new DeleteUserProfile;
        $response = $action->handle($request);

        expect($response)->toBeInstanceOf(RedirectResponse::class);
    });

    it('invalidates the session', function (): void {
        Auth::shouldReceive('logout')->once();

        $user = Mockery::mock(User::class);
        $user->shouldReceive('delete')->once();

        $request = Mockery::mock(DeleteUserProfileRequest::class);
        $request->shouldReceive('user')->once()->andReturn($user);
        $request->shouldReceive('session->invalidate')->once();
        $request->shouldReceive('session->regenerateToken')->once();

        $action = new DeleteUserProfile;
        $action->handle($request);
    });

    it('regenerates the session token', function (): void {
        Auth::shouldReceive('logout')->once();

        $user = Mockery::mock(User::class);
        $user->shouldReceive('delete')->once();

        $request = Mockery::mock(DeleteUserProfileRequest::class);
        $request->shouldReceive('user')->once()->andReturn($user);
        $request->shouldReceive('session->invalidate')->once();
        $request->shouldReceive('session->regenerateToken')->once();

        $action = new DeleteUserProfile;
        $action->handle($request);
    });

    it('deletes the user', function (): void {
        Auth::shouldReceive('logout')->once();

        $user = Mockery::mock(User::class);
        $user->shouldReceive('delete')->once();

        $request = Mockery::mock(DeleteUserProfileRequest::class);
        $request->shouldReceive('user')->once()->andReturn($user);
        $request->shouldReceive('session->invalidate')->once();
        $request->shouldReceive('session->regenerateToken')->once();

        $action = new DeleteUserProfile;
        $action->handle($request);
    });

    it('redirects to the login route', function (): void {
        Auth::shouldReceive('logout')->once();

        $user = Mockery::mock(User::class);
        $user->shouldReceive('delete')->once();

        $request = Mockery::mock(DeleteUserProfileRequest::class);
        $request->shouldReceive('user')->once()->andReturn($user);
        $request->shouldReceive('session->invalidate')->once();
        $request->shouldReceive('session->regenerateToken')->once();

        $action = new DeleteUserProfile;
        $response = $action->handle($request);

        expect($response)->toBeInstanceOf(RedirectResponse::class)
            ->and($response->getTargetUrl())->toContain(route('login'));
    });

    it('calls all required methods in the correct order', function (): void {
        $callOrder = [];

        Auth::shouldReceive('logout')->once()->andReturnUsing(function () use (&$callOrder): void {
            $callOrder[] = 'logout';
        });

        $user = Mockery::mock(User::class);
        $user->shouldReceive('delete')->once()->andReturnUsing(function () use (&$callOrder): true {
            $callOrder[] = 'delete';

            return true;
        });

        $request = Mockery::mock(DeleteUserProfileRequest::class);
        $request->shouldReceive('user')->once()->andReturn($user);
        $request->shouldReceive('session->invalidate')->once()->andReturnUsing(function () use (&$callOrder): void {
            $callOrder[] = 'invalidate';
        });
        $request->shouldReceive('session->regenerateToken')->once()->andReturnUsing(function () use (&$callOrder): void {
            $callOrder[] = 'regenerateToken';
        });

        $action = new DeleteUserProfile;
        $action->handle($request);

        expect($callOrder)->toBe(['logout', 'invalidate', 'regenerateToken', 'delete']);
    });

    it('works with the controller trait', function (): void {
        $traits = class_uses(DeleteUserProfile::class);
        expect($traits)->toHaveKey(AsController::class);
    });
});
