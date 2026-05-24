<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\MentorProgramObserver;
use Carbon\CarbonInterface;
use Database\Factories\MentorProgramFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

/**
 * @property CarbonInterface|null $start_time
 * @property CarbonInterface|null $end_time
 *
 * @mixin IdeHelperMentorProgram
 */
#[ObservedBy(MentorProgramObserver::class)]
#[UseFactory(MentorProgramFactory::class)]
#[Fillable([
    'mentor_id',
    'name',
    'slug',
    'is_main',
    'description',
    'cost',
    'currency_id',
    'start_time',
    'end_time',
    'session_duration',
    'session_type_options',
    'need_confirmation',
])]
class MentorProgram extends Model
{
    /** @use HasFactory<MentorProgramFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    /**
     * @return BelongsTo<Currency, $this>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * @return HasMany<MentorProgramBlock, $this>
     */
    public function mentorProgramBlocks(): HasMany
    {
        return $this->hasMany(MentorProgramBlock::class, 'mentor_program_id');
    }

    /**
     * @return BelongsToMany<MentorProfile, $this>
     */
    public function mentorProfiles(): BelongsToMany
    {
        return $this->belongsToMany(MentorProfile::class, 'mentor_profile_mentor_program');
    }

    /**
     * @return HasMany<MentorSession, $this>
     */
    public function mentorSession(): HasMany
    {
        return $this->hasMany(MentorSession::class, 'mentor_program_id');
    }

    /**
     * @return HasMany<CalendarEvent, $this>
     */
    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class, 'mentor_program_id');
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'is_main'              => 'boolean',
            'need_confirmation'    => 'boolean',
            'session_type_options' => 'array',
            'start_time'           => 'datetime',
            'end_time'             => 'datetime',
        ];
    }
}
