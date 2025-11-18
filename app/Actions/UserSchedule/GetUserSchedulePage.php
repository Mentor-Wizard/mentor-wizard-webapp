<?php

declare(strict_types=1);

namespace App\Actions\UserSchedule;

use App\Enums\UserScheduleRecordType;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;

class GetUserSchedulePage
{
    use AsController;

    public function handle(): Response
    {
        $user = Auth::user();

        $schedules = $user->schedules()
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return Inertia::render('UserSchedule/ListPage', [
            'schedules'     => $schedules,
            'scheduleTypes' => collect(UserScheduleRecordType::cases())->map(fn ($type) => [
                'value' => $type->value,
                'label' => $type->value,
            ]),
            'timezone' => $user->timezone ?? config('app.timezone'),
        ]);
    }
}
