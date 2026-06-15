<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CalendarProviderEnum;
use App\Enums\ExternalCalendarEventLogTypeEnum;
use Database\Factories\ExternalCalendarEventLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @property ExternalCalendarEventLogTypeEnum $type
 *
 * @mixin IdeHelperExternalCalendarEventLog
 */
#[Fillable([
    'external_calendar_event_id',
    'calendar_event_id',
    'user_id',
    'provider',
    'type',
    'message',
])]
class ExternalCalendarEventLog extends Model
{
    /** @use HasFactory<ExternalCalendarEventLogFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<ExternalCalendarEvent, $this>
     */
    public function externalCalendarEvent(): BelongsTo
    {
        return $this->belongsTo(ExternalCalendarEvent::class);
    }

    /**
     * @return BelongsTo<CalendarEvent, $this>
     */
    public function calendarEvent(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'provider' => CalendarProviderEnum::class,
            'type'     => ExternalCalendarEventLogTypeEnum::class,
        ];
    }
}
