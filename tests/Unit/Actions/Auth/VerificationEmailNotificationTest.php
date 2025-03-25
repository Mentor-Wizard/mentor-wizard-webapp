<?php

declare(strict_types=1);

use App\Actions\Auth\VerificationEmailNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

mutates(VerificationEmailNotification::class);

describe('VerificationEmailNotification', function () {
    it('redirects to dashboard when email is already verified', function () {
        $user = Mockery::mock('User');
        $user->shouldReceive('hasVerifiedEmail')->once()->andReturn(true);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->once()->andReturn($user);

        $action = new VerificationEmailNotification;

        $response = $action->handle($request);

        expect($response)->toBeInstanceOf(RedirectResponse::class)
            ->and($response->getTargetUrl())->toBe(route('pages.dashboard'));
    });

    it('sends email verification notification for unverified user', function () {
        $user = Mockery::mock('User');
        $user->shouldReceive('hasVerifiedEmail')->once()->andReturn(false);
        $user->shouldReceive('sendEmailVerificationNotification')->once();

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')->twice()->andReturn($user);

        $action = new VerificationEmailNotification;

        $response = $action->handle($request);

        expect($response)->toBeInstanceOf(RedirectResponse::class)
            ->and($response->getSession()->get('status'))->toBe('verification-link-sent');
    });
});
