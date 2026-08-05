<?php

declare(strict_types=1);

namespace Modules\ExternalCalendar\Enums;

enum ExternalCalendarEventSyncStatusEnum: string
{
    case Synced = 'synced';
    case Error = 'error';
}
