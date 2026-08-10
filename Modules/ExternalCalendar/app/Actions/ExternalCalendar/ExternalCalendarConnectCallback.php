<?php

declare(strict_types=1);

namespace Modules\ExternalCalendar\Actions\ExternalCalendar;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsController;
use Modules\ExternalCalendar\DTO\OAuthCallbackState;
use Modules\ExternalCalendar\Enums\CalendarProviderEnum;
use Modules\ExternalCalendar\Http\Requests\ExternalCalendarConnectCallbackRequest;
use Modules\ExternalCalendar\Services\ExternalCalendarSynchronizationService;
use Modules\ExternalCalendar\Traits\HandlesCalendarIntegrationCleanup;
use Throwable;

class ExternalCalendarConnectCallback
{
    use AsController;
    use HandlesCalendarIntegrationCleanup;

    public function __construct(
        private readonly ExternalCalendarSynchronizationService $synchronizationService,
    ) {}

    public function handle(ExternalCalendarConnectCallbackRequest $request): RedirectResponse
    {
        $state = $this->resolveStatePayload($request);

        if ($request->has('error')) {
            return $this->handleError($state, 'Authorization was denied or cancelled.');
        }

        if (! $state instanceof OAuthCallbackState) {
            return $this->handleError(null, 'Authorization session expired or invalid. Please try again.');
        }

        try {
            $this->synchronizationService->handleCallback($state->user, $state->enum, (string) $request->query('code'));
        } catch (Throwable $throwable) {
            Log::error('Calendar authorization failed.'.$throwable->getMessage());

            return $this->handleError($state, 'Failed to complete calendar authorization. Please try again.');
        }

        $result = $this->synchronizationService->fetchCalendars($state->user, $state->enum);

        if (! $result['success'] || empty($result['calendars'])) {
            return $this->handleError($state, $result['error'] ?? 'No calendars found on this account.');
        }

        return to_route('profile.edit', ['tab' => 'calendars'])
            ->with('calendar_provider', $state->enum->value)
            ->with('calendars', $result['calendars']);
    }

    private function handleError(?OAuthCallbackState $state, string $error): RedirectResponse
    {
        $this->cleanupIntegration($state?->user, $state?->enum);

        return to_route('profile.edit', ['tab' => 'calendars'])->with('error', $error);
    }

    /**
     * Resolves user + provider from the OAuth state parameter, falling back
     * to the session for providers (e.g. Outlook personal accounts) that do not
     * reliably return the state in the callback.
     */
    private function resolveStatePayload(ExternalCalendarConnectCallbackRequest $request): ?OAuthCallbackState
    {
        $rawState = (string) $request->query('state', '');

        if ($rawState !== '') {
            return $this->nonEmptyStateProcess($request, $rawState);
        }

        return $this->stateSavedInSessionProcess($request);
    }

    private function stateSavedInSessionProcess(ExternalCalendarConnectCallbackRequest $request): ?OAuthCallbackState
    {
        /** @var array{user_id: int, provider: string}|null $session */
        $session = $request->session()->pull('calendar_oauth_pending');

        if (isset($session['user_id'], $session['provider'])) {
            $user = User::query()->find($session['user_id']);
            $enum = CalendarProviderEnum::tryFrom($session['provider']);

            if ($user instanceof User && $enum instanceof CalendarProviderEnum) {
                return new OAuthCallbackState($user, $enum);
            }
        }

        return null;
    }

    private function nonEmptyStateProcess(ExternalCalendarConnectCallbackRequest $request, string $rawState): ?OAuthCallbackState
    {
        try {
            $payload = json_decode((string) decrypt($rawState), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $throwable) {
            Log::warning('OAuth state decryption failed — possible tampering or key rotation.', [
                'exception' => $throwable->getMessage(),
            ]);

            return null;
        }

        if (! is_array($payload) || ! isset($payload['user_id'], $payload['provider'])) {
            return null;
        }

        return $this->matchPendingSession($request, (int) $payload['user_id'], (string) $payload['provider']);
    }

    /**
     * Matches the state parameter against the single-use `calendar_oauth_pending`
     * entry written to the session when the flow started.
     *
     * The callback route deliberately sits outside the `auth` middleware group, so
     * the state parameter on its own must never be sufficient to act on an account:
     * OAuth state travels in URL query strings and leaks into provider logs, browser
     * history and referrer headers. Matching (and consuming) the session entry binds
     * the callback to the browser that started the flow and makes a captured state
     * value non-replayable.
     */
    private function matchPendingSession(ExternalCalendarConnectCallbackRequest $request, int $userId, string $provider): ?OAuthCallbackState
    {
        /** @var array{user_id: int, provider: string}|null $pending */
        $pending = $request->session()->pull('calendar_oauth_pending');

        if (! isset($pending['user_id'], $pending['provider'])
            || $pending['user_id'] !== $userId
            || $pending['provider'] !== $provider) {
            Log::warning('OAuth callback state did not match the pending session entry.', [
                'state_user_id' => $userId,
            ]);

            return null;
        }

        $user = User::query()->find($userId);
        $enum = CalendarProviderEnum::tryFrom($provider);

        if ($user instanceof User && $enum instanceof CalendarProviderEnum) {
            return new OAuthCallbackState($user, $enum);
        }

        return null;
    }
}
