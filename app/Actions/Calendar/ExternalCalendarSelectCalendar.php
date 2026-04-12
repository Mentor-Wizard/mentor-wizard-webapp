<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Http\Requests\Calendar\ExternalCalendarSelectCalendarRequest;
use App\Models\User;
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

        $this->synchronizationService->selectCalendar(
            $user,
            $request->resolveProvider(),
            $request->string('calendar_id')->toString(),
            $request->string('calendar_name')->toString(),
        );

        return back()->with('success', 'Calendar selected successfully.');
    }
}
