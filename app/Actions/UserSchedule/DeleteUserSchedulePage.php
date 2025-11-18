<?php

declare(strict_types=1);

namespace App\Actions\UserSchedule;

use App\Models\UserSchedule;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsController;
use Symfony\Component\HttpFoundation\Response;

class DeleteUserSchedulePage
{
    use AsController;

    public function handle(UserSchedule $userSchedule): Response
    {
        // Ensure user can only delete their own schedules
        if ($userSchedule->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $userSchedule->delete();

        return Inertia::location(route('user-schedule.index'));
    }
}
