<?php

declare(strict_types=1);

use App\Actions\Auth\GetConfirmPasswordPage;
use Inertia\Response;

describe('GetConfirmPasswordPage Unit Test', function (): void {
    it('should render the ConfirmPassword page', function (): void {
        $result = new GetConfirmPasswordPage;

        $result = $result->handle();

        $resultData = $result->toResponse(request())->getOriginalContent();

        expect($result)->toBeInstanceOf(Response::class)
            ->and(Arr::get($resultData->getData(), 'page.component'))->toBe('Auth/ConfirmPassword')
            ->and(Arr::get($resultData->getData(), 'page.props'))->toBeArray();
    });
});
