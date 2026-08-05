<?php

declare(strict_types=1);

namespace Modules\ExternalCalendar\Actions\ExternalCalendar;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;
use Modules\ExternalCalendar\Http\Requests\ExternalCalendarDisconnectRequest;
use Modules\ExternalCalendar\Models\UserCalendarIntegration;
use Modules\ExternalCalendar\Services\ExternalCalendarSynchronizationService;

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
