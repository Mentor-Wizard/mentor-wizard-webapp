<?php

declare(strict_types=1);

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Auth\Actions\Logout;

describe('Logout Action', function (): void {

    it('logs out the user', function (): void {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('session->invalidate')->once();
        $request->shouldReceive('session->regenerateToken')->once();

        $logout = new Logout;

        Auth::shouldReceive('guard')->with('web')
            ->once()
            ->andReturnSelf();
        Auth::shouldReceive('logout')->once();

        $result = $logout->handle($request);

        expect($result)->toBeInstanceOf(RedirectResponse::class)
            ->and($result->getTargetUrl())->toBe(route('login'));
    });
});
