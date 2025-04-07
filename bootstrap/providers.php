<?php

declare(strict_types=1);

$telescopeProviders = app()->environment('local', 'testing', 'ci') && class_exists(Laravel\Telescope\TelescopeServiceProvider::class)
    ? [
        Laravel\Telescope\TelescopeServiceProvider::class,
        App\Providers\TelescopeServiceProvider::class,
    ] : [];

return [
    App\Providers\AppServiceProvider::class,
    Spatie\Permission\PermissionServiceProvider::class,
    Olssonm\VeryBasicAuth\VeryBasicAuthServiceProvider::class,
    ...$telescopeProviders,
];
