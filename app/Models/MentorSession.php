<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MentorSessionFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @mixin IdeHelperMentorSession
 */
#[UseFactory(MentorSessionFactory::class)]
class MentorSession extends Model
{
    /** @use HasFactory<MentorSessionFactory> */
    use HasFactory;

    protected $fillable = [
        'mentor_id',
        'menti_id',
        'date',
        'is_success',
        'is_paid',
        'is_cancelled',
        'is_date_changed',
        'cost',
        'mentor_program_id',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function menti(): BelongsTo
    {
        return $this->belongsTo(User::class, 'menti_id');
    }

    /**
     * @return HasOne<Payment, $this>
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * @return HasOne<MentorSessionNote, $this>
     */
    public function mentorSessionNote(): HasOne
    {
        return $this->hasOne(MentorSessionNote::class);
    }

    public function calendarEvent():HasOne
    {
        return $this->hasOne(CalendarEvent::class);
    }

    public function mentorProgram():HasOne
    {
        return $this->hasOne(MentorProgram::class);
    }


    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mentor_id'       => 'int',
            'menti_id'        => 'int',
            'date'            => 'datetime',
            'is_success'      => 'boolean',
            'is_paid'         => 'boolean',
            'is_cancelled'    => 'boolean',
            'is_date_changed' => 'boolean',
            'cost'            => 'float',
        ];
    }
}
