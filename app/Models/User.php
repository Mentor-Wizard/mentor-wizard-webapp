<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RoleGuardEnum;
use App\Observers\UserObserver;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @mixin IdeHelperUser
 */
#[ObservedBy(UserObserver::class)]
#[UseFactory(UserFactory::class)]
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory;
    use HasRoles;
    use Notifiable;

    public const DEFAULT_PASSWORD_LENGHT = 8;

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

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function profile(): ?HasOne
    {
        return $this->hasOne(UserProfile::class);
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
}
