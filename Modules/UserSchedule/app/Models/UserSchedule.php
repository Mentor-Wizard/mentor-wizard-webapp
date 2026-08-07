<?php

declare(strict_types=1);

namespace Modules\UserSchedule\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserSchedule\Database\Factories\UserScheduleFactory;
use Modules\UserSchedule\Enums\UserScheduleRecordType;
use Override;

/**
 * @mixin IdeHelperUserSchedule
 */
#[UseFactory(UserScheduleFactory::class)]
#[Fillable([
    'user_id',
    'day_of_week',
    'start_time',
    'end_time',
    'type',
    'day_off_date',
])]
class UserSchedule extends Model
{
    /** @use HasFactory<UserScheduleFactory> */
    use HasFactory;

    const int MAX_NUMBER_OF_SCHEDULES_PERIODS_PER_DAY = 4;

    const int MAX_NUMBER_OF_ACTIVE_DAY_OFF_EXCLUSIONS = 30;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'type'         => UserScheduleRecordType::class,
            'day_off_date' => 'date',
        ];
    }
}
