<?php

declare(strict_types=1);

namespace Modules\MentorSession\Providers;

use Nwidart\Modules\Support\ModuleServiceProvider;
use Override;

class MentorSessionServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    #[Override]
    protected string $name = 'MentorSession';

    /**
     * The lowercase version of the module name.
     */
    #[Override]
    protected string $nameLower = 'mentorsession';

    /**
     * Provider classes to register.
     *
     * @var string[]
     */
    #[Override]
    protected array $providers = [];
}
