<?php

declare(strict_types=1);

namespace Modules\UserSchedule\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\UserSchedule\Enums\UserScheduleRecordType;
use Modules\UserSchedule\Models\UserSchedule;

/**
 * @phpstan-require-extends Model
 */
trait HasUserSchedules
{
    /**
     * @return HasMany<UserSchedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(UserSchedule::class);
    }

    /**
     * @return HasMany<UserSchedule, $this>
     */
    public function activeScheduleRecords(): HasMany
    {
        return $this->schedules()
            ->where('type', '!=', UserScheduleRecordType::DAY_OFF->value)
            ->orWhere('type', '=', UserScheduleRecordType::DAY_OFF->value)
            ->where('day_off_date', '>=', now()->format('Y-m-d'));
    }
}
