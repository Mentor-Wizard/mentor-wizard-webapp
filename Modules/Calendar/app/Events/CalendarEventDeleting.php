<?php

declare(strict_types=1);

namespace Modules\Calendar\Events;

use Illuminate\Foundation\Events\Dispatchable;

final readonly class CalendarEventDeleting
{
    use Dispatchable;

    public function __construct(
        public int $calendarEventId,
    ) {}
}
