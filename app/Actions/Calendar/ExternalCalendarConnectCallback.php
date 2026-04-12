<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Enums\CalendarProviderEnum;
use App\Http\Requests\Calendar\ExternalCalendarConnectCallbackRequest;
use App\Models\User;
use App\Services\ExternalCalendar\ExternalCalendarSynchronizationService;
use App\Traits\Calendar\HandlesCalendarIntegrationCleanup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsController;
use Throwable;

class ExternalCalendarConnectCallback
{
    use AsController;
    use HandlesCalendarIntegrationCleanup;

    protected ?User $user = null;

    protected ?CalendarProviderEnum $enum = null;

    public function __construct(
        private readonly ExternalCalendarSynchronizationService $synchronizationService,
    ) {}

    public function handle(ExternalCalendarConnectCallbackRequest $request): RedirectResponse
    {
        $statePayload = $this->resolveStatePayload($request);

        if ($request->has('error')) {
            $this->cleanupIntegration($this->user, $this->enum);

            return to_route('profile.edit')
                ->with('error', 'Authorization was denied or cancelled.');
        }

        if ($statePayload === null || $this->user === null || $this->enum === null) {
            return to_route('profile.edit')
                ->with('error', 'Authorization session expired or invalid. Please try again.');
        }

        try {
            $this->synchronizationService->handleCallback($this->user, $this->enum, (string) $request->query('code'));
        } catch (Throwable) {
            $this->cleanupIntegration($this->user, $this->enum);

            return to_route('profile.edit')
                ->with('error', 'Failed to complete calendar authorization. Please try again.');
        }

        $result = $this->synchronizationService->fetchCalendars($this->user, $this->enum);

        if (! $result['success'] || empty($result['calendars'])) {
            $this->cleanupIntegration($this->user, $this->enum);

            $error = $result['error'] ?? 'No calendars found on this account.';

            return to_route('profile.edit')->with('error', $error);
        }

        return to_route('profile.edit')
            ->with('calendar_provider', $this->enum->value)
            ->with('calendars', $result['calendars']);
    }

    /**
     * Resolves user_id + provider from the OAuth state parameter, falling back
     * to the session for providers (e.g. Outlook personal accounts) that do not
     * reliably return the state in the callback.
     *
     * Sets {@see $user} and {@see $enum} as side effects when the payload is valid.
     *
     * @return array{user_id: int, provider: string}|null
     */
    private function resolveStatePayload(ExternalCalendarConnectCallbackRequest $request): ?array
    {
        $rawState = (string) $request->query('state', '');

        if ($rawState !== '') {
            try {
                $payload = json_decode((string) decrypt($rawState), true);

                if (is_array($payload) && isset($payload['user_id'], $payload['provider'])) {
                    $request->session()->forget('calendar_oauth_pending');

                    $this->user = User::query()->find((int) $payload['user_id']);
                    $this->enum = CalendarProviderEnum::tryFrom($payload['provider']);

                    return $payload;
                }
            } catch (Throwable $e) {
                Log::warning('OAuth state decryption failed — possible tampering or key rotation.', [
                    'exception' => $e->getMessage(),
                ]);

                $this->cleanupIntegration($this->user, $this->enum);

                return null;
            }
        }

        /** @var array{user_id: int, provider: string}|null $session */
        $session = $request->session()->pull('calendar_oauth_pending');

        if (isset($session['user_id'], $session['provider'])) {
            $this->user = User::query()->find($session['user_id']);
            $this->enum = CalendarProviderEnum::tryFrom($session['provider']);

            return $session;
        }

        return null;
    }
}
