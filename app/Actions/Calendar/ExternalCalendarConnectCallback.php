<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Enums\CalendarProviderEnum;
use App\Models\User;
use App\Services\ExternalCalendar\ExternalCalendarSynchronizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsController;

class ExternalCalendarConnectCallback
{
    use AsController;

    public function __construct(
        private readonly ExternalCalendarSynchronizationService $synchronizationService,
    ) {}

    public function asController(Request $request, string $provider): RedirectResponse
    {
        if ($request->has('error')) {
            return to_route('pages.settings.external-calendar')
                ->with('error', 'Google authorization was denied or cancelled.');
        }

        /** @var array{user_id: int, provider: string} $state */
        $state = json_decode(decrypt((string) $request->query('state', '')), true);

        $user = User::query()->findOrFail($state['user_id']);
        $enum = CalendarProviderEnum::from($state['provider']);

        $this->synchronizationService->handleCallback($user, $enum, (string) $request->query('code'));

        $result = $this->synchronizationService->fetchCalendars($user, $enum);

        if (! $result['success'] || empty($result['calendars'])) {
            $error = $result['error'] ?? 'No calendars found on this Google account.';

            return to_route('pages.settings.external-calendar')->with('error', $error);
        }

        return to_route('pages.settings.external-calendar')->with('calendars', $result['calendars']);
    }
}
