<?php

declare(strict_types=1);

namespace Modules\UserProfile\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use Override;

class UserProfileServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    #[Override]
    protected string $name = 'UserProfile';

    /**
     * The lowercase version of the module name.
     */
    #[Override]
    protected string $nameLower = 'userprofile';

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    #[Override]
    protected array $providers = [
        RouteServiceProvider::class,
    ];

    #[Override]
    public function boot(): void
    {
        parent::boot();
    }
}
