<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Override;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    #[Override]
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    #[Override]
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth'    => [
                'user'   => $request->user()?->load([
                    'roles',
                    'permissions',
                    'profile',
                ]),
                'avatar' => $request->user()?->profile?->avatar,
                'roles' => $request->user()?->getRoleNames(),
            ],
            'ziggy'   => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'project' => [
                'name' => config('app.name'),
            ],
        ];
    }
}
