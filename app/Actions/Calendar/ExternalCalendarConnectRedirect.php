<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Enums\CalendarProviderEnum;
use App\Models\User;
use App\Services\ExternalCalendar\ExternalCalendarSynchronizationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Lorisleiva\Actions\Concerns\AsController;
use Symfony\Component\HttpFoundation\Response;

class ExternalCalendarConnectRedirect
{
    use AsController;

    public function __construct(
        private readonly ExternalCalendarSynchronizationService $synchronizationService,
    ) {}

    public function asController(Request $request, string $provider): InertiaResponse|Response
    {
        abort_unless(CalendarProviderEnum::isValid($provider), Response::HTTP_UNPROCESSABLE_ENTITY);

        $calendarProvider = CalendarProviderEnum::from($provider);

        $clientId = null;
        $clientSecret = null;

        if (! $calendarProvider->usesAppCredentials()) {
            $request->validate([
                'client_id'     => ['required', 'string', 'min:10'],
                'client_secret' => ['required', 'string', 'min:10'],
            ]);

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
