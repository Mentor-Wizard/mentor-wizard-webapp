<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Http\Requests\Calendar\ExternalCalendarConnectRedirectRequest;
use App\Models\User;
use App\Services\ExternalCalendar\ExternalCalendarSynchronizationService;
use App\Traits\Calendar\HandlesCalendarIntegrationCleanup;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Lorisleiva\Actions\Concerns\AsController;
use Symfony\Component\HttpFoundation\Response;

class ExternalCalendarConnectRedirect
{
    use AsController;
    use HandlesCalendarIntegrationCleanup;

    public function __construct(
        private readonly ExternalCalendarSynchronizationService $synchronizationService,
    ) {}

    public function handle(ExternalCalendarConnectRedirectRequest $request): InertiaResponse|Response
    {
        $calendarProvider = $request->resolveProvider();

        $clientId = null;
        $clientSecret = null;

        if (! $calendarProvider->usesAppCredentials()) {
            $clientId = $request->string('client_id')->toString();
            $clientSecret = $request->string('client_secret')->toString();
        }

        /** @var User $user */
        $user = $request->user();

        $oauthUrl = $this->synchronizationService->saveCredentialsAndBuildOAuthUrl(
            $user,
            $calendarProvider,
            $clientId,
            $clientSecret,
        );

        $request->session()->put('calendar_oauth_pending', [
            'user_id'  => $user->getKey(),
            'provider' => $calendarProvider->value,
        ]);

        return Inertia::location($oauthUrl);
    }
}
