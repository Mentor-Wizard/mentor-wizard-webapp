<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CalendarProviderEnum;
use App\Enums\ExternalCalendarEventSyncStatusEnum;
use Database\Factories\ExternalCalendarEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * @property CalendarProviderEnum $provider
 * @property ExternalCalendarEventSyncStatusEnum|null $sync_status
 *
 * @mixin IdeHelperExternalCalendarEvent
 */
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
