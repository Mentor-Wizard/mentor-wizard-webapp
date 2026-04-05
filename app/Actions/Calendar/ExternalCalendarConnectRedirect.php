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
        if (! CalendarProviderEnum::isValid($provider)) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $request->validate([
            'client_id'     => ['required', 'string', 'min:10'],
            'client_secret' => ['required', 'string', 'min:10'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $oauthUrl = $this->synchronizationService->saveCredentialsAndBuildOAuthUrl(
            $user,
            CalendarProviderEnum::from($provider),
            $request->string('client_id')->toString(),
            $request->string('client_secret')->toString(),
        );

        return Inertia::location($oauthUrl);
    }
}
