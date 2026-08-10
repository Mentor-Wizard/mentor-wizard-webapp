<?php

declare(strict_types=1);

namespace Modules\ExternalCalendar\Enums;

enum ExternalCalendarEventLogTypeEnum: string
{
    case Error = 'error';
    case Success = 'success';
    case Info = 'info';
    case ErrorStatusViewed = 'error_status_viewed';
}
