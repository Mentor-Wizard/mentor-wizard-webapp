<?php

declare(strict_types=1);

namespace Modules\ExternalCalendar\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\ExternalCalendar\Models\UserCalendarIntegration;

/**
 * @phpstan-require-extends Model
 */
trait HasExternalCalendarIntegrations
{
    /**
     * @return HasMany<UserCalendarIntegration, $this>
     */
    public function calendarIntegrations(): HasMany
    {
        return $this->hasMany(UserCalendarIntegration::class);
    }
}
