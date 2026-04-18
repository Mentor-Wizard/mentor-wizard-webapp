<?php

declare(strict_types=1);

namespace App\Actions\Calendar\ExternalCalendar;

use App\Http\Requests\Calendar\ExternalCalendarDisconnectRequest;
use App\Models\User;
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

        $this->synchronizationService->disconnect($user, $request->resolveProvider());

        return to_route('profile.edit')
            ->with('success', 'Calendar disconnected successfully.');
    }
}
