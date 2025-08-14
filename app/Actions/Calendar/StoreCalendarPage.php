<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Enums\EventRoleEnum;
use App\Http\Requests\Calendar\StoreEventRequest;
use App\Models\Event;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsController;
use Symfony\Component\HttpFoundation\Response;

class StoreCalendarPage
{
    use AsController;

    public function handle(StoreEventRequest $request): Response
    {
        if (! auth()->user()->hasRole('mentor')) {
            return response()->json(['message' => 'Only mentee can create events.'], Response::HTTP_FORBIDDEN);
        }

        $event = Event::query()->create([
            ...$request->getEventData(),
        ]);

        $event->users()->attach(auth()->id(), [
            'role'       => EventRoleEnum::HOST->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Inertia::location(route('pages.calendar'));
    }
}
