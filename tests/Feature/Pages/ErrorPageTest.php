<?php

declare(strict_types=1);
use Inertia\Testing\AssertableInertia as Assert;

it('Visited an unregistered page', function () {
    $response = $this->get('/page-not-found');

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Error')
        ->whereAll([
            'status' => 404,
        ])
    );
});
