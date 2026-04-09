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

class ExternalCalendarConnectDirect
{
    use AsController;

    public function __construct(
        private readonly ExternalCalendarSynchronizationService $synchronizationService,
    ) {}

    public function asController(Request $request, string $provider): RedirectResponse
    {
        if (! CalendarProviderEnum::isValid($provider)) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $calendarProvider = CalendarProviderEnum::from($provider);

        if (! $calendarProvider->isCalDav()) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $request->validate([
            'client_id'     => ['required', 'string', 'email'],
            'client_secret' => ['required', 'string', 'min:10'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $this->synchronizationService->saveCredentials(
            $user,
            $calendarProvider,
            $request->string('client_id')->toString(),
            $request->string('client_secret')->toString(),
        );

        $result = $this->synchronizationService->fetchCalendars($user, $calendarProvider);

        if (! $result['success'] || empty($result['calendars'])) {
            $error = $result['error'] ?? 'No calendars found. Check your Apple ID and App-Specific Password.';

            return to_route('profile.edit')->with('error', $error);
        }

        return to_route('profile.edit')->with('calendars', $result['calendars']);
    }
}
