<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Http\Requests\Calendar\EditEventRequest;
use App\Models\Event;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsController;
use Symfony\Component\HttpFoundation\Response;

class EditCalendarPage
{
    use AsController;

    public function handle(EditEventRequest $request, string $id): Response
    {
        if (! auth()->user()->hasRole('mentor')) {
            return response()->json(['message' => 'Only mentor can create events.'], Response::HTTP_FORBIDDEN);
        }

        Event::query()->where('id', $id)->update([
            ...$request->getEventData(),
        ]);

        return Inertia::location(route('pages.calendar'));
    }
}
