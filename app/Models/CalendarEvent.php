<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CalendarEventStatusEnum;
use App\Observers\CalendarEventObserver;
use App\Policies\CalendarEventPolicy;
use Database\Factories\CalendarEventFactory;
use Database\Factories\CurrencyFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
 * @property int|null $mentor_session_id
 *
 * @mixin IdeHelperCalendarEvent
 */
#[UsePolicy(CalendarEventPolicy::class)]
#[UseFactory(CalendarEventFactory::class)]
#[ObservedBy(CalendarEventObserver::class)]
class CalendarEvent extends Model
{
    /** @use HasFactory<CurrencyFactory> */
    use HasFactory;

    const MAXIMUM_NUMBER_OF_MONTHS_EVENT_CAN_BE_SET = 6;

    const ROUNDING_DISCRECY_TIME_IN_MINUTES = 5;

    protected $fillable = [
        'title',
        'status',
        'start_date_time',
        'end_date_time',
        'date',
        'type',
        'web_link',
        'description',
        'mentor_program_id',
        'mentor_session_id',
    ];

    /**
     * @return BelongsToMany<User, CalendarEvent>
     */
    public function calendarEventUsers(): BelongsToMany
    {
        /** @phpstan-ignore-next-line */
        return $this->belongsToMany(User::class,
            'calendar_event_user', 'calendar_event_id')
            ->withPivot('colour', 'confirmed_at', 'role')
            ->withTimestamps();
    }

    /**
     * @return BelongsTo<MentorSession, $this>
     */
    public function mentorSession(): BelongsTo
    {
        return $this->belongsTo(MentorSession::class);
    }

    /**
     * @return BelongsTo<MentorProgram, $this>
     */
    public function mentorProgram(): BelongsTo
    {
        return $this->belongsTo(MentorProgram::class);
    }

    /** Get the event duration in minutes. */
    /**
     * @return Attribute<int, never>
     */
    protected function duration(): Attribute
    {
        return Attribute::make(
            get: fn (): int => (int) $this->start_date_time->diffInMinutes($this->end_date_time),
        );
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
