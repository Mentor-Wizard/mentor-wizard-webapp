<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Database\Factories\MentorSessionFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Override;

/**
 * @mixin IdeHelperMentorSession
 */
#[UseFactory(MentorSessionFactory::class)]
#[Fillable([
    'mentor_id',
    'menti_id',
    'date',
    'is_success',
    'is_paid',
    'is_cancelled',
    'is_date_changed',
    'cost',
    'mentor_program_id',
])]
class MentorSession extends Model
{
    /** @use HasFactory<MentorSessionFactory> */
    use HasFactory;

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

    /**
     * @return HasOne<CalendarEvent, $this>
     */
    public function calendarEvent(): HasOne
    {
        return $this->hasOne(CalendarEvent::class);
    }

    /**
     * @return BelongsTo<MentorProgram, $this>
     */
    public function mentorProgram(): BelongsTo
    {
        return $this->belongsTo(MentorProgram::class);
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'mentor_id'         => 'int',
            'menti_id'          => 'int',
            'mentor_program_id' => 'int',
            'date'              => 'datetime',
            'is_success'        => 'boolean',
            'is_paid'           => 'boolean',
            'is_cancelled'      => 'boolean',
            'is_date_changed'   => 'boolean',
            'cost'              => 'float',
        ];
    }
}
