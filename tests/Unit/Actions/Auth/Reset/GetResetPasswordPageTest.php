<?php

declare(strict_types=1);

use App\Actions\Auth\Reset\GetResetPasswordPage;
use Inertia\Response;

mutates(GetResetPasswordPage::class);

describe('GetResetPasswordPage Unit Test', function () {
    it('returns Inertia view with session status', function () {
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

    it('returns the correct Inertia page with session status', function () {
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
