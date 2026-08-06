<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RoleGuardEnum;
use App\Enums\UserScheduleRecordType;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\HasName;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\Visible;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\Auth\Observers\UserObserver;
use Modules\Calendar\Traits\HasCalendarEvents;
use Modules\Chat\Models\Chat;
use Modules\ExternalCalendar\Traits\HasExternalCalendarIntegrations;
use Modules\MentorProgram\Traits\HasMentorPrograms;
use Override;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property-read UserProfile $profile
 * @property-read MentorProfile|null $mentorProfile
 * @property-read float $rating
 * @property-read Pivot $pivot
 * @property string $username
 *
 * @mixin IdeHelperUser
 */
#[ObservedBy(UserObserver::class)]
#[UseFactory(UserFactory::class)]
#[Fillable([
    'user_id',
    'username',
    'email',
    'password',
    'preferences',
])]
#[Hidden([
    'password',
    'remember_token',
])]
#[Visible([
    'id',
    'username',
    'slug',
    'email',
    'created_at',
    'updated_at',
    'profile',
    'media',
])]
class User extends Authenticatable implements HasMedia, HasName, MustVerifyEmail
{
    use HasCalendarEvents;
    use HasExternalCalendarIntegrations;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasMentorPrograms;
    use HasRoles;
    use InteractsWithMedia;
    use Notifiable;

    public const int MIN_PASSWORD_LENGTH = 8;

    public const int DEFAULT_MENTOR_PAGE_PAGINATION = 10;

    public const int NOTIFICATIONS_PER_PAGE = 20;

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
     * @return BelongsToMany<Chat, $this>
     */
    public function chats(): BelongsToMany
    {
        return $this->belongsToMany(Chat::class, 'chat_users')
            ->withPivot(['status', 'is_muted'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<UserSchedule, $this>
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(UserSchedule::class);
    }

    /**
     * @return HasMany<UserSchedule, $this>
     */
    public function activeScheduleRecords(): HasMany
    {
        return $this->schedules()
            ->where('type', '!=', UserScheduleRecordType::DAY_OFF->value)
            ->orWhere('type', '=', UserScheduleRecordType::DAY_OFF->value)
            ->where('day_off_date', '>=', now()->format('Y-m-d'));
    }

    /**
     * Pins the broadcast notification channel to a legacy, namespace-independent
     * name so it survives any future move of this class into a module
     * (`Relation::enforceMorphMap()` does not cover this — see
     * `Illuminate\Notifications\Events\BroadcastNotificationCreated::channelName()`,
     * which uses the raw `get_class()`, not `getMorphClass()`).
     */
    public function receivesBroadcastNotificationsOn(): string
    {
        return 'App.Models.User.'.$this->getKey();
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
    #[Override]
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }
}
