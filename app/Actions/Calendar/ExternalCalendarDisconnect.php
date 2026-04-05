<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Enums\CalendarProviderEnum;
use App\Models\User;
use App\Services\ExternalCalendar\ExternalCalendarSynchronizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsController;
use Symfony\Component\HttpFoundation\Response;

class ExternalCalendarDisconnect
{
    use AsController;

    public function __construct(
        private readonly ExternalCalendarSynchronizationService $synchronizationService,
    ) {}

    public function handle(User $user, CalendarProviderEnum $provider): void
    {
        $this->synchronizationService->disconnect($user, $provider);
    }

    public function asController(Request $request, string $provider): RedirectResponse
    {
        if (! CalendarProviderEnum::isValid($provider)) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        /** @var User $user */
        $user = $request->user();

        $this->handle($user, CalendarProviderEnum::from($provider));

        return to_route('pages.settings.external-calendar');
    }
}
