<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithCachedConfig;
use Illuminate\Foundation\Testing\WithCachedRoutes;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->use(WithCachedConfig::class)
    ->use(WithCachedRoutes::class)
    ->in('Feature', 'Unit');

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->use(WithCachedConfig::class)
    ->use(WithCachedRoutes::class)
    ->in('Integration');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', fn () => $this->toBe(1));

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

use Inertia\Response as InertiaResponse;

function inertiaProps(InertiaResponse $response): array
{
    $httpResponse = $response->toResponse(request());

    $original = $httpResponse->getOriginalContent();
    if (is_object($original) && method_exists($original, 'getData')) {
        $data = $original->getData();
        if (is_array($data) && isset($data['page']) && is_array($data['page']) && isset($data['page']['props'])) {
            $props = $data['page']['props'];

            return is_array($props) ? $props : (array) $props;
        }
    }

    // Fallback via reflection for different Inertia versions
    try {
        $ref = new ReflectionClass($response);
        if ($ref->hasProperty('props')) {
            $prop = $ref->getProperty('props');
            $props = $prop->getValue($response);

            return is_array($props) ? $props : (array) $props;
        }
    } catch (Throwable) {
        // ignore
    }

    return [];
}
