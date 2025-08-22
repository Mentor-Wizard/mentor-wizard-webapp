<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RoleGuardEnum;
use App\Observers\UserObserver;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property-read UserProfile $profile
 * @property string $username
 *
 * @mixin IdeHelperUser
 */
#[ObservedBy(UserObserver::class)]
#[UseFactory(UserFactory::class)]
class User extends Authenticatable implements HasMedia, MustVerifyEmail
{
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

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function mentorProfile(): ?HasOne
    {
        return $this->hasOne(MentorProfile::class);
    }

    public function mentiProgramProgress(): ?HasOne
    {
        return $this->hasOne(MentorProgramBlockProgress::class);
    }

    public function mentorReviews(): HasMany
    {
        return $this->hasMany(MentorReview::class, 'mentor_id');
    }

    public function reviewsByMenti(): HasMany
    {
        return $this->hasMany(MentorReview::class, 'menti_id');
    }

    public function mentorPrograms(): HasMany
    {
        return $this->hasMany(MentorProgram::class, 'mentor_id');
    }

    public function mentorSessions(): HasMany
    {
        return $this->hasMany(MentorSession::class, 'mentor_id');
    }

    public function mentiSessions(): HasMany
    {
        return $this->hasMany(MentorSession::class, 'menti_id');
    }

    public function mentorChats(): HasMany
    {
        return $this->hasMany(Chat::class, 'mentor_id');
    }

    public function mentiChats(): HasMany
    {
        return $this->hasMany(Chat::class, 'menti_id');
    }

    public function coachChats(): HasMany
    {
        return $this->hasMany(Chat::class, 'coach_id');
    }

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
