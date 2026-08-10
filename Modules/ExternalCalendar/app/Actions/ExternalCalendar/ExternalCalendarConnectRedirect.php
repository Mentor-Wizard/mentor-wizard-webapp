<?php

declare(strict_types=1);

namespace Modules\ExternalCalendar\Actions\ExternalCalendar;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Lorisleiva\Actions\Concerns\AsController;
use Modules\ExternalCalendar\Http\Requests\ExternalCalendarConnectRedirectRequest;
use Modules\ExternalCalendar\Services\ExternalCalendarSynchronizationService;
use Modules\ExternalCalendar\Traits\HandlesCalendarIntegrationCleanup;
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
