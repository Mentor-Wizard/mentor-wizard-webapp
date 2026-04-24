<?php

declare(strict_types=1);

namespace App\Actions\Calendar\ExternalCalendar;

use App\Http\Requests\Calendar\ExternalCalendar\ExternalCalendarDisconnectRequest;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use App\Services\ExternalCalendar\ExternalCalendarSynchronizationService;
use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;

class ExternalCalendarDisconnect
{
    use AsController;

    public function __construct(
        private readonly ExternalCalendarSynchronizationService $synchronizationService,
    ) {}

    public function handle(ExternalCalendarDisconnectRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $integration = UserCalendarIntegration::query()
            ->where('user_id', $user->getKey())
            ->where('provider', $request->resolveProvider())
            ->first();

        abort_if($integration && $user->cannot('delete', $integration), 403);

        $this->synchronizationService->disconnect($user, $request->resolveProvider());

        return to_route('profile.edit')
            ->with('success', 'Calendar disconnected successfully.');
    }
}
