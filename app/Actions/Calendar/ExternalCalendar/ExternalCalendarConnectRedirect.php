<?php

declare(strict_types=1);

namespace App\Actions\Calendar\ExternalCalendar;

use App\Http\Requests\Calendar\ExternalCalendar\ExternalCalendarConnectRedirectRequest;
use App\Models\User;
use App\Services\ExternalCalendar\ExternalCalendarSynchronizationService;
use App\Traits\ExternalCalendar\HandlesCalendarIntegrationCleanup;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Lorisleiva\Actions\Concerns\AsController;
use Symfony\Component\HttpFoundation\Response;

class ExternalCalendarConnectRedirect
{
    use AsController;
    use HandlesCalendarIntegrationCleanup;

    private ?string $clientId = null;

    private ?string $clientSecret = null;

    public function __construct(
        private readonly ExternalCalendarSynchronizationService $synchronizationService,
    ) {}

    public function handle(ExternalCalendarConnectRedirectRequest $request): InertiaResponse|Response
    {
        $calendarProvider = $request->resolveProvider();

        if (! $calendarProvider->usesAppCredentials()) {
            $this->clientId = $request->string('client_id')->toString();
            $this->clientSecret = $request->string('client_secret')->toString();
        }

        /** @var User $user */
        $user = $request->user();

        $oauthUrl = $this->synchronizationService->saveCredentialsAndBuildOAuthUrl(
            $user,
            $calendarProvider,
            $this->clientId,
            $this->clientSecret,
        );

        $request->session()->put('calendar_oauth_pending', [
            'user_id'  => $user->getKey(),
            'provider' => $calendarProvider->value,
        ]);

        return Inertia::location($oauthUrl);
    }
}
