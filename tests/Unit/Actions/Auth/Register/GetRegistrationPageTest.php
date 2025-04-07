<?php

declare(strict_types=1);

use App\Actions\Auth\Register\GetRegistrationPage;
use Inertia\Response;

mutates(GetRegistrationPage::class);

describe('GetRegistrationPage Action', function () {

    it('returns correct Inertia response', function () {
        $action = new GetRegistrationPage;

        $response = $action->handle();
        $responseData = $response->toResponse(request())->getOriginalContent();

        expect($response)->toBeInstanceOf(Response::class)
            ->and(Arr::get($responseData->getData(), 'page.component'))->toBe('Auth/Register');
    });
});
