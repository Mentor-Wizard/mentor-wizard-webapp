<?php

declare(strict_types=1);

namespace Modules\MentorProgram\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\MentorProgram\Models\MentorProgram;
use Modules\MentorProgram\Policies\MentorProgramPolicy;
use Nwidart\Modules\Support\ModuleServiceProvider;
use Override;

class MentorProgramServiceProvider extends ModuleServiceProvider
{
    /**
     * The name of the module.
     */
    #[Override]
    protected string $name = 'MentorProgram';

    /**
     * The lowercase version of the module name.
     */
    #[Override]
    protected string $nameLower = 'mentorprogram';

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

        Gate::policy(MentorProgram::class, MentorProgramPolicy::class);
    }
}
