<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CalendarEventStatusEnum;
use App\Observers\MentorProgramObserver;
use Database\Factories\MentorProgramFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperMentorProgram
 */
#[ObservedBy(MentorProgramObserver::class)]
#[UseFactory(MentorProgramFactory::class)]
class MentorProgram extends Model
{
    /** @use HasFactory<MentorProgramFactory> */
    use HasFactory;

    protected $fillable = [
        'mentor_id',
        'name',
        'slug',
        'description',
        'cost',
        'currency_id',
        'start_time',
        'end_time',
        'session_duration',
        'session_duration_options',
    ];

    protected $appends = [
        'pending_events_requests_number',
        'confirmed_events_number',
    ];

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

    public function mentorSession(): HasMany
    {
        return $this->hasMany(MentorSession::class, 'mentor_program_id');
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class, 'mentor_program_id');
    }

    protected function pendingEventsRequestsNumber(): Attribute
    {
        return Attribute::make(get: fn () => $this->calendarEvents()
            ->where('status', CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION)
            ->count());
    }

    protected function confirmedEventsNumber(): Attribute
    {
        return Attribute::make(get: fn () => $this->calendarEvents()
            ->where('status', CalendarEventStatusEnum::CONFIRMED)
            ->count());
    }
}
