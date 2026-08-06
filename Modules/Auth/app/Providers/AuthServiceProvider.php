<?php

declare(strict_types=1);

namespace Modules\Auth\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use Override;

/**
 * Bootstraps the Auth (Identity & Access) module.
 *
 * Deliberately does not publish/merge a `config/config.php` file (D-A,
 * docs/plans/identity-domain-migration/02-development-plan-backend.md §1):
 * `ModuleServiceProvider::registerConfig()` merges any module `config/*.php`
 * into the framework config namespace derived from `$nameLower` — for this
 * module that namespace is `auth`, i.e. `config/auth.php`. A module config
 * file here would silently overwrite framework auth config on merge/publish.
 * `tests/Unit/ArchTest.php` enforces `Modules/Auth/config` stays free of
 * `.php` files as a structural guard for this invariant.
 */
class AuthServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    #[Override]
    protected string $name = 'Auth';

    /**
     * The lowercase version of the module name.
     */
    #[Override]
    protected string $nameLower = 'auth';

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    #[Override]
    protected array $providers = [
        RouteServiceProvider::class,
    ];
}
