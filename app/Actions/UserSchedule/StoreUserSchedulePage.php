<?php

declare(strict_types=1);

namespace App\Actions\UserSchedule;

use App\Http\Requests\UserSchedule\StoreUserScheduleRequest;
use App\Models\UserSchedule;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Lorisleiva\Actions\Concerns\AsController;
use Symfony\Component\HttpFoundation\Response;

class StoreUserSchedulePage
{
    use AsController;

    public function handle(StoreUserScheduleRequest $request): Response
    {
        UserSchedule::query()->create([
            ...$request->validated(),
            'user_id' => Auth::id(),
        ]);

        return Inertia::location(route('user-schedule.index'));
    }
}
