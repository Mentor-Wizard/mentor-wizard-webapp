<?php

declare(strict_types=1);

namespace Modules\Calendar\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Calendar\Models\CalendarEvent;

final readonly class CalendarEventConfirmed
{
    use Dispatchable;

    public function __construct(
        public CalendarEvent $calendarEvent,
    ) {}
}
