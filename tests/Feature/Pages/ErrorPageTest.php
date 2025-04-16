<?php

declare(strict_types=1);

use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia as Assert;

it('Visited an unregistered page', function (): void {
    $response = $this->get('/page-not-found');

    $response->assertInertia(fn (Assert $page): AssertableJson => $page
        ->component('Error')
        ->whereAll([
            'status' => 404,
        ])
    );
});
