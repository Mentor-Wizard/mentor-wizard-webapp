<?php

declare(strict_types=1);

namespace App\Models;

use App\Policies\CalendarEventPolicy;
use Database\Factories\CalendarEventFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $start_date_time
 * @property Carbon $end_date_time
 * @property string $date
 * @property string $title
 * @property string $status
 * @property int $duration
 * @property string $type
 * @property string|null $web_link
 * @property string|null $description
 * @property int|null $mentor_program_id
 *
 * @mixin IdeHelperCalendarEvent
 */
#[UsePolicy(CalendarEventPolicy::class)]
#[UseFactory(CalendarEventFactory::class)]
class CalendarEvent extends Model
{
    use HasFactory;

    const int MAXIMUM_NUMBER_OF_MONTHS_EVENT_CAN_BE_SET = 6;

    protected $fillable = [
        'title',
        'status',
        'start_date_time',
        'end_date_time',
        'duration',
        'date',
        'type',
        'web_link',
        'description',
        'mentor_program_id',
    ];

    public function calendarEventUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'calendar_event_user', 'calendar_event_id')
            // FIXME навіщо нам тут колір?
            ->withPivot('colour')
            ->withTimestamps();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date_time'   => 'datetime',
            'end_date_time'     => 'datetime',
        ];
    }
}
