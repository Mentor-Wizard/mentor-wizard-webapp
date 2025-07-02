<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    App\Providers\TelescopeServiceProvider::class,
    Laravel\Telescope\TelescopeServiceProvider::class,
    Olssonm\VeryBasicAuth\VeryBasicAuthServiceProvider::class,
    Spatie\Permission\PermissionServiceProvider::class,
];
