<?php

declare(strict_types=1);

namespace App\Enums;

enum ExternalCalendarEventSyncStatusEnum: string
{
    case Synced = 'synced';
    case Error = 'error';
}
