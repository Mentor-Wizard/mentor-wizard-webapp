<?php

declare(strict_types=1);

use App\Actions\Pages\WelcomePage;
use Symfony\Component\HttpFoundation\Response;

covers(WelcomePage::class);

it('returns a successful response', function () {
    $response = $this->get('/');

    $response->assertStatus(Response::HTTP_OK);
});
