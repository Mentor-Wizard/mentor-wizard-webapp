<?php

declare(strict_types=1);

namespace App\Actions\Pages\UserSchedule;

use App\Enums\UserScheduleRecordType;
use App\Http\Resources\UserSchedule\UserScheduleViewResource;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;

class GetUserSchedulePage
{
    use AsController;

    public function handle(): Response
    {
        $schedules = auth()->user()->activeScheduleRecords()
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return Inertia::render('UserSchedule/ListPage', [
            'schedules'     => UserScheduleViewResource::collection($schedules)->resolve(),
            'scheduleTypes' => UserScheduleRecordType::getCollection(),
            'timezone'      => $schedules?->first()->timezone ?? null,
        ]);
    }
}
