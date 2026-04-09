<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Enums\CalendarProviderEnum;
use App\Models\User;
use App\Services\ExternalCalendar\ExternalCalendarSynchronizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsController;
use Throwable;

class ExternalCalendarConnectCallback
{
    use AsController;

    public function __construct(
        private readonly ExternalCalendarSynchronizationService $synchronizationService,
    ) {}

    public function asController(Request $request, string $provider): RedirectResponse
    {
        if ($request->has('error')) {
            return to_route('profile.edit')
                ->with('error', 'Authorization was denied or cancelled.');
        }

        $statePayload = $this->resolveStatePayload($request);

        if ($statePayload === null) {
            return to_route('profile.edit')
                ->with('error', 'Authorization session expired or invalid. Please try again.');
        }

        $user = User::query()->findOrFail($statePayload['user_id']);
        $enum = CalendarProviderEnum::from($statePayload['provider']);

        $this->synchronizationService->handleCallback($user, $enum, (string) $request->query('code'));

        $result = $this->synchronizationService->fetchCalendars($user, $enum);

        if (! $result['success'] || empty($result['calendars'])) {
            $error = $result['error'] ?? 'No calendars found on this Google account.';

            return to_route('profile.edit')->with('error', $error);
        }

        return to_route('profile.edit')
            ->with('calendar_provider', $enum->value)
            ->with('calendars', $result['calendars']);
    }

    /**
     * Resolves user_id + provider from the OAuth state parameter, falling back
     * to the session for providers (e.g. Outlook personal accounts) that do not
     * reliably return the state in the callback.
     *
     * @return array{user_id: int, provider: string}|null
     */
    private function resolveStatePayload(Request $request): ?array
    {
        $rawState = (string) $request->query('state', '');

        if ($rawState !== '') {
            try {
                /** @var array{user_id: int, provider: string} $payload */
                $payload = json_decode(decrypt($rawState), true);

                if (isset($payload['user_id'], $payload['provider'])) {
                    $request->session()->forget('calendar_oauth_pending');

                    return $payload;
                }
            } catch (Throwable) {
                // state present but could not be decrypted — fall through to session
            }
        }

        /** @var array{user_id: int, provider: string}|null $session */
        $session = $request->session()->pull('calendar_oauth_pending');

        return isset($session['user_id'], $session['provider']) ? $session : null;
    }
}
