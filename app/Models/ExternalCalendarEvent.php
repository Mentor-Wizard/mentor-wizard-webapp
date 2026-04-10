<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CalendarProviderEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperExternalCalendarEvent
 */
class ExternalCalendarEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'calendar_event_id',
        'user_id',
        'provider',
        'external_event_id',
    ];

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
    protected function casts(): array
    {
        return [
            'provider' => CalendarProviderEnum::class,
        ];
    }
}
