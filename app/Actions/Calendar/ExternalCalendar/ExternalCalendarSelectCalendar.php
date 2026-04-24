<?php

declare(strict_types=1);

namespace App\Actions\Calendar\ExternalCalendar;

use App\Http\Requests\Calendar\ExternalCalendar\ExternalCalendarSelectCalendarRequest;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use App\Services\ExternalCalendar\ExternalCalendarSynchronizationService;
use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;

class ExternalCalendarSelectCalendar
{
    use AsController;

    public function __construct(
        private readonly ExternalCalendarSynchronizationService $synchronizationService,
    ) {}

    public function handle(ExternalCalendarSelectCalendarRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $integration = UserCalendarIntegration::query()
            ->where('user_id', $user->getKey())
            ->where('provider', $request->resolveProvider())
            ->firstOrFail();

        abort_if($user->cannot('update', $integration), 403);

        $this->synchronizationService->selectCalendar(
            $user,
            $request->resolveProvider(),
            $request->string('calendar_id')->toString(),
            $request->string('calendar_name')->toString(),
        );

        return back()->with('success', 'Calendar selected successfully.');
    }
}
