<?php

declare(strict_types=1);

use Inertia\Response;
use Modules\Auth\Actions\Reset\GetResetPasswordPage;

describe('GetResetPasswordPage Unit Test', function (): void {
    it('returns Inertia view with session status', function (): void {
        $action = new GetResetPasswordPage;

        session(['status' => 'test-status']);

        $result = $action->handle();
        $resultData = $result->toResponse(request())->getOriginalContent();

        expect($result)->toBeInstanceOf(Response::class)
            ->and(Arr::get($resultData->getData(), 'page.component'))->toBe('Auth/ForgotPassword')
            ->and(Arr::get($resultData->getData(), 'page.props'))->toEqual([
                'status' => 'test-status',
            ]);
    });

    it('returns the correct Inertia page with session status', function (): void {
        $action = new GetResetPasswordPage;
        session()->forget('status');

        $result = $action->handle();
        $resultData = $result->toResponse(request())->getOriginalContent();

        expect($result)->toBeInstanceOf(Response::class)
            ->and(Arr::get($resultData->getData(), 'page.component'))->toBe('Auth/ForgotPassword')
            ->and(Arr::get($resultData->getData(), 'page.props'))->toEqual([
                'status' => null,
            ]);
    });
});
