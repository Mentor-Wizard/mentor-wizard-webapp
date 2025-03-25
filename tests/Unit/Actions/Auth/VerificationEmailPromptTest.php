<?php

declare(strict_types=1);

use App\Actions\Auth\VerificationEmailPrompt;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\Request;
use Inertia\Response;

mutates(VerificationEmailPrompt::class);

describe('VerificationEmailPrompt Unit Test', function () {
    beforeEach(function () {
        $this->seed(RoleSeeder::class);
    });

    it('renders verify email page with status when email is not verified', function () {
        $status = 'verification-link-sent';
        session(['status' => $status]);

        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')
            ->once()
            ->andReturn($user);

        $action = new VerificationEmailPrompt;
        $result = $action->handle($request);
        $resultData = $result->toResponse(request())->getOriginalContent();

        expect($result)
            ->toBeInstanceOf(Response::class)
            ->and(Arr::get($resultData->getData(), 'page.component'))
            ->toBe('Auth/VerifyEmail')
            ->and(Arr::get($resultData->getData(), 'page.props.status'))
            ->toBe($status);
    });

    it('renders verify email page with null status when no status in session', function () {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('user')
            ->once()
            ->andReturn($user);

        $action = new VerificationEmailPrompt;
        $result = $action->handle($request);
        $resultData = $result->toResponse(request())->getOriginalContent();

        expect($result)
            ->toBeInstanceOf(Response::class)
            ->and(Arr::get($resultData->getData(), 'page.component'))
            ->toBe('Auth/VerifyEmail')
            ->and(Arr::get($resultData->getData(), 'page.props.status'))
            ->toBeNull();
    });
});
