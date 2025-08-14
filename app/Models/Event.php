<?php

namespace App\Models;

use Database\Factories\CurrencyFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @mixin IdeHelperEvent
 */
class Event extends Model
{
    /** @use HasFactory<CurrencyFactory> */
    use HasFactory;

    protected $fillable = [
        'unique_id',
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

    public function users(): belongsToMany
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
