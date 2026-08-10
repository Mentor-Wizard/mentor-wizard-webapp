<?php

declare(strict_types=1);

namespace Modules\UserSchedule\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\UserSchedule\Models\UserSchedule;
use Modules\UserSchedule\Policies\UserSchedulePolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;
use Override;

class UserScheduleServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    #[Override]
    protected string $name = 'UserSchedule';

    /**
     * The lowercase version of the module name.
     */
    #[Override]
    protected string $nameLower = 'userschedule';

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

        Gate::policy(UserSchedule::class, UserSchedulePolicy::class);
    }
}
