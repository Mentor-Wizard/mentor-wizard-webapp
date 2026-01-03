<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserScheduleRecordType;
use Database\Factories\UserScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperUserSchedule
 */
#[UseFactory(UserScheduleFactory::class)]
class UserSchedule extends Model
{
    /** @use HasFactory<UserScheduleFactory> */
    use HasFactory;

    const int MAX_NUMBER_OF_SCHEDULES_PERIODS_PER_DAY = 4;

    const int MAX_NUMBER_OF_ACTIVE_DAY_OFF_EXCLUSIONS = 30;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'day_of_week',
        'start_time',
        'end_time',
        'type',
        'day_off_date',
    ];

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
    protected function casts(): array
    {
        return [
            'type'         => UserScheduleRecordType::class,
            'day_off_date' => 'date',
        ];
    }
}
