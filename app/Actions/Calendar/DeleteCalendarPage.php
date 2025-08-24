<?php

declare(strict_types=1);

namespace App\Actions\Calendar;

use App\Enums\EventRoleEnum;
use App\Models\Event;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsController;
use Symfony\Component\HttpFoundation\Response;

class DeleteCalendarPage
{
    use AsController;

    public function handle(string $id): Response
    {
        if (! auth()->user()->hasRole('mentor')) {
            return response()->json(['message' => 'Only mentor can create events.'], Response::HTTP_FORBIDDEN);
        }

        $event = Event::query()->where('id', $id)->first();
        if (! $event) {
            return response()->json(['message' => 'Event not found'], Response::HTTP_NOT_FOUND);
        }

        if (! $event->users()->where('user_id', auth()->id())
            ->where('role', EventRoleEnum::HOST)->exists()) {
            return response()->json(['message' => 'Attempt to delete event of other mentor'], Response::HTTP_FORBIDDEN);
        }

        Event::query()->where('id', $id)->delete();

        return Inertia::location(route('pages.calendar'));
    }
}
