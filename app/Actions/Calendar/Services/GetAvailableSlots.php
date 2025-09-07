<?php

declare(strict_types=1);

namespace App\Actions\Calendar\Services;

use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Carbon;

class GetAvailableSlots
{
    private array $availableSlots = [];

    public function __construct(private readonly User $user, private readonly string $timezone) {}

    public function execute()
    {
        $currentDate = Carbon::now('UTC');
        $currentDatetimeStampUTC = $currentDate->timestamp;
        $currentDatetimeStampTimezone = Carbon::now($this->timezone)->timestamp;
        $events = $this->user->events()->where('start_date_time', '>=', $currentDate)->orderBy('start_date_time')->get();
        if ($events->isEmpty()) {
            return [];
        }

        $previousEvent = null;
        foreach ($events as $event) {
            /** @var Event $event */
            if (is_null($previousEvent)) {
                if ($currentDatetimeStampUTC < $event->start_date_time->timestamp) {
                    $this->availableSlots[] = ['start' => $currentDatetimeStampTimezone,
                        'end'                          => $event->start_date_time->setTimezone($this->timezone)->timestamp];
                }
            } else {
                $this->availableSlots[] = ['start' => $previousEvent->end_date_time->setTimezone($this->timezone)->timestamp,
                    'end'                          => $event->start_date_time->setTimezone($this->timezone)->timestamp];
            }

            $previousEvent = $event;
        }

        $this->availableSlots[] = ['start' => $previousEvent->end_date_time->setTimezone($this->timezone)->timestamp,
            'end'                          => Carbon::now($this->timezone)->addMonths(2)->timestamp];

        return $this->availableSlots;
    }
}
