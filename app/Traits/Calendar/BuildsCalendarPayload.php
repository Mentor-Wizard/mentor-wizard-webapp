<?php

declare(strict_types=1);

namespace App\Traits\Calendar;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;

trait BuildsCalendarPayload
{
    /**
     * Build common payload structure for a calendar day.
     *
     * @param  array<int|string, mixed>  $eventsData
     * @return array<string, bool|string>
     */
    protected function buildDayPayload(
        CarbonInterface $date,
        CarbonInterface $referenceDate,
        array $eventsData,
    ): array {
        $payload = ['date' => $date->format('Y-m-d')];

        if ($date->isSameMonth($referenceDate)) {
            $payload['isCurrentMonth'] = true;
        }

        if ($date->isSameDay($referenceDate)) {
            $payload['isSelected'] = true;
        }

        if (Date::now($this->timezone)->isSameDay($date)) {
            $payload['isToday'] = true;
        }

        if (in_array($date->format('Y-m-d'), $eventsData, true)) {
            $payload['hasEvent'] = true;
        }

        return $payload;
    }
}
