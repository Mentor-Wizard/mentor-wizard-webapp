<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CalendarEventRoleEnum;
use App\Enums\RoleGuardEnum;
use App\Enums\UserScheduleRecordType;
use App\Observers\UserObserver;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\HasName;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property-read UserProfile $profile
 * @property-read MentorProfile|null $mentorProfile
 * @property-read float $rating
 * @property-read Pivot $pivot
 * @property string $username
 * @mixin IdeHelperUser
 */
#[ObservedBy(UserObserver::class)]
#[UseFactory(UserFactory::class)]
class User extends Authenticatable implements HasMedia, HasName, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasRoles;
    use InteractsWithMedia;
    use Notifiable;

    public const int MIN_PASSWORD_LENGTH = 8;

    public const int DEFAULT_MENTOR_PAGE_PAGINATION = 10;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @var string[]
     */
    protected array $guard_name = [
        'web',
        RoleGuardEnum::USER->value,
        RoleGuardEnum::ADMIN->value,
        RoleGuardEnum::SUPER_ADMIN->value,
        RoleGuardEnum::MENTOR->value,
        RoleGuardEnum::MENTI->value,
        RoleGuardEnum::COACH->value,
    ];

    protected $visible = [
        'id',
        'username',
        'email',
        'created_at',
        'updated_at',
        'profile',
        'media',
    ];

    /**
     * @return HasOne<UserProfile, $this>
     */
    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    /**
     * @return HasOne<MentorProfile, $this>
     */
    public function mentorProfile(): HasOne
    {
        return $this->hasOne(MentorProfile::class);
    }

    /**
     * @return HasOne<MentorProgramBlockProgress, $this>
     */
    public function mentiProgramProgress(): HasOne
    {
        return $this->hasOne(MentorProgramBlockProgress::class, 'menti_id');
    }

    /**
     * @return HasMany<MentorReview, $this>
     */
    public function mentorReviews(): HasMany
    {
        return $this->hasMany(MentorReview::class, 'mentor_id');
    }

    /**
     * @return HasMany<MentorReview, $this>
     */
    public function reviewsByMenti(): HasMany
    {
        return $this->hasMany(MentorReview::class, 'menti_id');
    }

    /**
     * @return HasMany<MentorProgram, $this>
     */
    public function mentorPrograms(): HasMany
    {
        return $this->hasMany(MentorProgram::class, 'mentor_id');
    }

    /**
     * @return HasMany<MentorSession, $this>
     */
    public function mentorSessions(): HasMany
    {
        return $this->hasMany(MentorSession::class, 'mentor_id');
    }

    /**
     * @return HasMany<MentorSession, $this>
     */
    public function mentiSessions(): HasMany
    {
        return $this->hasMany(MentorSession::class, 'menti_id');
    }

    /**
     * @return HasMany<Chat, $this>
     */
    public function mentorChats(): HasMany
    {
        return $this->hasMany(Chat::class, 'mentor_id');
    }

    /**
     * @return HasMany<Chat, $this>
     */
    public function mentiChats(): HasMany
    {
        return $this->hasMany(Chat::class, 'menti_id');
    }

    /**
     * @return BelongsToMany<CalendarEvent, static>
     */
    public function calendarEvents(): BelongsToMany
    {
        /** @phpstan-ignore-next-line */
        return $this->belongsToMany(CalendarEvent::class, 'calendar_event_user', 'user_id')
            ->withPivot('colour')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<CalendarEvent, static>
     */
    public function hostedCalendarEvents(): BelongsToMany
    {
        return $this->calendarEvents()
            ->wherePivot('role', CalendarEventRoleEnum::HOST);
    }

    /**
     * @return BelongsToMany<CalendarEvent, static>
     */
    public function participatingCalendarEvents(): BelongsToMany
    {
        return $this->calendarEvents()
            ->wherePivot('role', CalendarEventRoleEnum::PARTICIPANT);
    }

    /**
     * @return HasMany<Chat, $this>
     */
    public function coachChats(): HasMany
    {
        return $this->hasMany(Chat::class, 'coach_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(UserSchedule::class);
    }

    public function activeScheduleRecords(): HasMany
    {
        return $this->schedules()
            ->where('type', '!=', UserScheduleRecordType::DAY_OFF->value)
            ->orWhere('type', '=', UserScheduleRecordType::DAY_OFF->value)
            ->where('day_off_date', '>=', now()->format('Y-m-d'));
    }

    public function getFilamentName(): string
    {
        return $this->username ?? '';
    }

    /**
     * @return Attribute<float, never>
     */
    protected function rating(): Attribute
    {
        return Attribute::make(
            get: fn (): float => (float) $this->mentorReviews()->avg('rating'),
        );
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }
}
