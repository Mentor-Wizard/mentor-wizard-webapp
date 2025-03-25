<?php

declare(strict_types=1);

use App\Http\Requests\Auth\VerifyEmailRequest;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;

mutates(VerifyEmailRequest::class);

describe('VerifyEmailRequest Authorization', function () {
    it('authorizes request when user and hash match without string casting', function () {
        $user = Mockery::mock('User');
        $user->shouldReceive('getKey')->once()->andReturn(123);
        $user->shouldReceive('getEmailForVerification')->once()->andReturn('test@example.com');

        $request = Mockery::mock(VerifyEmailRequest::class)
            ->makePartial()
            ->shouldReceive('user')
            ->twice()
            ->andReturn($user)
            ->getMock();

        $request->shouldReceive('route')
            ->with('id')
            ->once()
            ->andReturn(123);

        $request->shouldReceive('route')
            ->with('hash')
            ->once()
            ->andReturn(sha1('test@example.com'));

        expect($request->authorize())->toBeTrue();
    });

    it('authorizes request when user and hash match with string casting', function () {
        $user = Mockery::mock('User');
        $user->shouldReceive('getKey')->once()->andReturn(123);
        $user->shouldReceive('getEmailForVerification')->once()->andReturn('test@example.com');

        $request = Mockery::mock(VerifyEmailRequest::class)
            ->makePartial()
            ->shouldReceive('user')
            ->twice()
            ->andReturn($user)
            ->getMock();

        $request->shouldReceive('route')
            ->with('id')
            ->once()
            ->andReturn('123');

        $request->shouldReceive('route')
            ->with('hash')
            ->once()
            ->andReturn(sha1('test@example.com'));

        expect($request->authorize())->toBeTrue();
    });

    it('denies authorization when user id does not match', function () {
        $user = Mockery::mock('User');
        $user->shouldReceive('getKey')->once()->andReturn(123);

        $request = Mockery::mock(VerifyEmailRequest::class)
            ->makePartial()
            ->shouldReceive('user')
            ->once()
            ->andReturn($user)
            ->getMock();

        $request->shouldReceive('route')
            ->with('id')
            ->once()
            ->andReturn('456');

        expect($request->authorize())->toBeFalse();
    });

    it('denies authorization when hash does not match', function () {
        $user = Mockery::mock('User');
        $user->shouldReceive('getKey')->once()->andReturn(123);
        $user->shouldReceive('getEmailForVerification')->once()->andReturn('test@example.com');

        $request = Mockery::mock(VerifyEmailRequest::class)
            ->makePartial()
            ->shouldReceive('user')
            ->twice()
            ->andReturn($user)
            ->getMock();

        $request->shouldReceive('route')
            ->with('id')
            ->once()
            ->andReturn('123');

        $request->shouldReceive('route')
            ->with('hash')
            ->once()
            ->andReturn('invalid_hash');

        expect($request->authorize())->toBeFalse();
    });
});

describe('VerifyEmailRequest Fulfill', function () {
    it('marks email as verified and dispatches event when email is not verified', function () {
        $user = Mockery::mock('User');
        $user->shouldReceive('hasVerifiedEmail')->once()->andReturn(false);
        $user->shouldReceive('markEmailAsVerified')->once();

        Event::fake();

        $request = Mockery::mock(VerifyEmailRequest::class)
            ->makePartial()
            ->shouldReceive('user')
            ->times(3)
            ->andReturn($user)
            ->getMock();

        $request->fulfill();

        Event::assertDispatched(Verified::class);
    });

    it('does not mark email as verified or dispatch event when email is already verified', function () {
        $user = Mockery::mock('User');
        $user->shouldReceive('hasVerifiedEmail')->once()->andReturn(true);
        $user->shouldNotReceive('markEmailAsVerified');

        Event::fake();

        $request = Mockery::mock(VerifyEmailRequest::class)
            ->makePartial()
            ->shouldReceive('user')
            ->once()
            ->andReturn($user)
            ->getMock();

        $request->fulfill();

        Event::assertNotDispatched(Verified::class);
    });
});

describe('VerifyEmailRequest Validation', function () {
    it('returns empty rules array', function () {
        $request = new VerifyEmailRequest;

        expect($request->rules())->toBe([]);
    });
});
