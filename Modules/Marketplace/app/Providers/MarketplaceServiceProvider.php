<?php

declare(strict_types=1);

namespace Modules\Marketplace\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use Override;

class MarketplaceServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    #[Override]
    protected string $name = 'Marketplace';

    /**
     * The lowercase version of the module name.
     */
    #[Override]
    protected string $nameLower = 'marketplace';

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
