<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Enums\CalendarProviderEnum;
use App\Models\User;
use App\Services\ExternalCalendar\ExternalCalendarSynchronizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsController;
use Symfony\Component\HttpFoundation\Response;

class ExternalCalendarSelectCalendar
{
    use AsController;

    public function __construct(
        private readonly ExternalCalendarSynchronizationService $synchronizationService,
    ) {}

    public function asController(Request $request, string $provider): RedirectResponse
    {
        abort_unless(CalendarProviderEnum::isValid($provider), Response::HTTP_UNPROCESSABLE_ENTITY);

        $request->validate([
            'calendar_id'   => ['required', 'string'],
            'calendar_name' => ['required', 'string'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $this->synchronizationService->selectCalendar(
            $user,
            CalendarProviderEnum::from($provider),
            $request->string('calendar_id')->toString(),
            $request->string('calendar_name')->toString(),
        );

        return back()->with('success', 'Calendar selected successfully.');
    }
}
