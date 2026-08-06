<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RoleGuardEnum;
use App\Enums\UserScheduleRecordType;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\HasName;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\Visible;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Modules\Auth\Observers\UserObserver;
use Modules\Calendar\Models\CalendarEvent;
use Modules\Calendar\Traits\HasCalendarEvents;
use Modules\Chat\Models\Chat;
use Modules\ExternalCalendar\Models\UserCalendarIntegration;
use Modules\ExternalCalendar\Traits\HasExternalCalendarIntegrations;
use Modules\Marketplace\Models\MentorProfile;
use Modules\Marketplace\Models\MentorReview;
use Modules\Marketplace\Traits\HasMentorProfile;
use Modules\MentorProgram\Models\MentorProgram;
use Modules\MentorProgram\Models\MentorProgramBlockProgress;
use Modules\MentorProgram\Traits\HasMentorPrograms;
use Modules\UserProfile\Models\UserProfile;
use Modules\UserProfile\Traits\HasUserProfile;
use Override;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property-read UserProfile $profile
 * @property-read MentorProfile|null $mentorProfile
 * @property-read float $rating
 * @property-read Pivot $pivot
 * @property string $username
 * @property int $id
 * @property string $email
 * @property CarbonImmutable|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property string|null $slug
 * @property string|null $preferences
 * @property-read Collection<int, UserSchedule> $activeScheduleRecords
 * @property-read int|null $active_schedule_records_count
 * @property-read Collection<int, CalendarEvent> $calendarEvents
 * @property-read int|null $calendar_events_count
 * @property-read Collection<int, UserCalendarIntegration> $calendarIntegrations
 * @property-read int|null $calendar_integrations_count
 * @property-read Collection<int, Chat> $chats
 * @property-read int|null $chats_count
 * @property-read Collection<int, CalendarEvent> $hostedCalendarEvents
 * @property-read int|null $hosted_calendar_events_count
 * @property-read MediaCollection<int, Media> $media
 * @property-read int|null $media_count
 * @property-read MentorProgramBlockProgress|null $mentiProgramProgress
 * @property-read Collection<int, MentorSession> $mentiSessions
 * @property-read int|null $menti_sessions_count
 * @property-read Collection<int, MentorProgram> $mentorPrograms
 * @property-read int|null $mentor_programs_count
 * @property-read Collection<int, MentorReview> $mentorReviews
 * @property-read int|null $mentor_reviews_count
 * @property-read Collection<int, MentorSession> $mentorSessions
 * @property-read int|null $mentor_sessions_count
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read Collection<int, CalendarEvent> $participatingCalendarEvents
 * @property-read int|null $participating_calendar_events_count
 * @property-read Collection<int, Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read Collection<int, MentorReview> $reviewsByMenti
 * @property-read int|null $reviews_by_menti_count
 * @property-read Collection<int, Role> $roles
 * @property-read int|null $roles_count
 * @property-read Collection<int, UserSchedule> $schedules
 * @property-read int|null $schedules_count
 * @property-read Collection<int, Permission> $teams
 * @property-read int|null $teams_count
 *
 * @method static UserFactory factory($count = null, $state = [])
 * @method static Builder<static>|User newModelQuery()
 * @method static Builder<static>|User newQuery()
 * @method static Builder<static>|User permission($permissions, bool $without = false)
 * @method static Builder<static>|User query()
 * @method static Builder<static>|User role($roles, ?string $guard = null, bool $without = false)
 * @method static Builder<static>|User team($teams, bool $without = false)
 * @method static Builder<static>|User whereCreatedAt($value)
 * @method static Builder<static>|User whereEmail($value)
 * @method static Builder<static>|User whereEmailVerifiedAt($value)
 * @method static Builder<static>|User whereId($value)
 * @method static Builder<static>|User wherePassword($value)
 * @method static Builder<static>|User wherePreferences($value)
 * @method static Builder<static>|User whereRememberToken($value)
 * @method static Builder<static>|User whereSlug($value)
 * @method static Builder<static>|User whereUpdatedAt($value)
 * @method static Builder<static>|User whereUsername($value)
 * @method static Builder<static>|User withoutPermission($permissions)
 * @method static Builder<static>|User withoutRole($roles, ?string $guard = null)
 * @method static Builder<static>|User withoutTeam($teams)
 *
 * @mixin \Eloquent
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

    use HasMentorProfile;
    use HasMentorPrograms;
    use HasRoles;
    use HasUserProfile;
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
