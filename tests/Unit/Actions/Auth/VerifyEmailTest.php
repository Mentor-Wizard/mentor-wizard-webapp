<?php

declare(strict_types=1);

use App\Actions\Auth\VerifyEmail;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;

mutates(VerifyEmail::class);

describe('VerifyEmail Action', function () {
    beforeEach(function () {
        $this->seed(RoleSeeder::class);
    });

    it('redirects to dashboard if email already verified', function () {
        $user = User::factory()->create();

        $request = Mockery::mock(VerifyEmailRequest::class);
        $request->shouldReceive('user')->once()->andReturn($user);

        $action = new VerifyEmail;
        $result = $action->handle($request);

        expect($result->getTargetUrl())
            ->toContain(route('pages.dashboard'))
            ->toContain('verified=1');
    });

    it('marks email as verified and dispatches verified event', function () {
        Event::fake();

        $user = User::factory()->unverified()->create();

        $request = Mockery::mock(VerifyEmailRequest::class);
        $request->shouldReceive('user')
            ->times(3)
            ->andReturn($user);

        $action = new VerifyEmail;
        $result = $action->handle($request);

        expect($result->getTargetUrl())
            ->toContain(route('pages.dashboard'))
            ->toContain('verified=1');

        Event::assertDispatched(Verified::class, function (Verified $event) use ($user) {
            return $event->user === $user;
        });
    });
});
