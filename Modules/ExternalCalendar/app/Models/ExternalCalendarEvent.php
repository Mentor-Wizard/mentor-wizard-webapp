<?php

declare(strict_types=1);

namespace Modules\ExternalCalendar\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Calendar\Models\CalendarEvent;
use Modules\ExternalCalendar\Database\Factories\ExternalCalendarEventFactory;
use Modules\ExternalCalendar\Enums\CalendarProviderEnum;
use Modules\ExternalCalendar\Enums\ExternalCalendarEventSyncStatusEnum;
use Override;

/**
 * @property CalendarProviderEnum $provider
 * @property ExternalCalendarEventSyncStatusEnum|null $sync_status
 *
 * @mixin IdeHelperExternalCalendarEvent
 */
#[UseFactory(ExternalCalendarEventFactory::class)]
#[Fillable([
    'calendar_event_id',
    'user_id',
    'provider',
    'external_event_id',
    'sync_status',
])]
class ExternalCalendarEvent extends Model
{
    /** @use HasFactory<ExternalCalendarEventFactory> */
    use HasFactory;

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
     * @return HasMany<ExternalCalendarEventLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(ExternalCalendarEventLog::class);
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'provider'    => CalendarProviderEnum::class,
            'sync_status' => ExternalCalendarEventSyncStatusEnum::class,
        ];
    }
}
