<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CurrencyFactory;
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
 * @mixin IdeHelperEvent
 */
class Event extends Model
{
    /** @use HasFactory<CurrencyFactory> */
    use HasFactory;

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

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
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
