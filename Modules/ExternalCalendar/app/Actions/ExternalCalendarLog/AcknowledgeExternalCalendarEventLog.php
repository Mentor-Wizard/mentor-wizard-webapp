<?php

declare(strict_types=1);

namespace Modules\ExternalCalendar\Actions\ExternalCalendarLog;

use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;
use Modules\ExternalCalendar\Enums\ExternalCalendarEventLogTypeEnum;
use Modules\ExternalCalendar\Models\ExternalCalendarEventLog;

class AcknowledgeExternalCalendarEventLog
{
    use AsController;

    public function handle(ExternalCalendarEventLog $log): RedirectResponse
    {
        if ($log->type !== ExternalCalendarEventLogTypeEnum::Error) {
            return back()->with('error', 'Only error logs can be acknowledged.');
        }

        $log->update(['type' => ExternalCalendarEventLogTypeEnum::ErrorStatusViewed]);

        return back()->with('success', 'Error acknowledged.');
    }
}
