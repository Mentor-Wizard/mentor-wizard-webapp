<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Http\Requests\Calendar\ExternalCalendarConnectDirectRequest;
use App\Models\User;
use App\Services\ExternalCalendar\ExternalCalendarSynchronizationService;
use App\Traits\Calendar\HandlesCalendarIntegrationCleanup;
use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;

class ExternalCalendarConnectDirect
{
    use AsController;
    use HandlesCalendarIntegrationCleanup;

    public function __construct(
        private readonly ExternalCalendarSynchronizationService $synchronizationService,
    ) {}

    public function handle(ExternalCalendarConnectDirectRequest $request): RedirectResponse
    {
        $calendarProvider = $request->resolveProvider();

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
            $this->cleanupIntegration($user, $calendarProvider);

            $error = $result['error'] ?? 'No calendars found. Check your Apple ID and App-Specific Password.';

            return to_route('profile.edit')->with('error', $error);
        }

        return to_route('profile.edit')
            ->with('calendar_provider', $calendarProvider->value)
            ->with('calendars', $result['calendars']);
    }
}
