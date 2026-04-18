<?php

declare(strict_types=1);

namespace App\Actions\Calendar\ExternalCalendarLog;

use App\Enums\ExternalCalendarEventLogTypeEnum;
use App\Models\ExternalCalendarEventLog;
use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsController;

class AcknowledgeExternalCalendarEventLog
{
    use AsController;

    public function handle(ExternalCalendarEventLog $log): RedirectResponse
    {
        $log->update(['type' => ExternalCalendarEventLogTypeEnum::ErrorStatusViewed]);

        return back()->with('success', 'Error acknowledged.');
    }
}
