<?php

declare(strict_types=1);

namespace Modules\UserProfile\Models;

use App\Models\Currency;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Attributes\Visible;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\UserProfile\Database\Factories\UserProfileFactory;
use Override;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property-read string $avatar URL of the avatar image
 *
 * @mixin IdeHelperUserProfile
 */
#[UseFactory(UserProfileFactory::class)]
#[Appends([
    'avatar',
])]
// TODO(OQ-1): `timezone` and `minimum_pre_booking_time` are concept-owned by
// Calendar/BookingPreferences (Modules/Calendar reads `minimum_pre_booking_time`
// via $user->profile), not UserProfile. Migrated here "as is" per the approved
// plan; not moved to Calendar in this PR. See docs/plans/userprofile-module-migration.
#[Fillable([
    'user_id',
    'name',
    'last_name',
    'linkedin',
    'telegram',
    'whatsapp',
    'phone',
    'is_mute',
    'cost_per_hour',
    'currency_id',
    'timezone',
    'minimum_pre_booking_time',
])]
#[Visible([
    'id',
    'name',
    'last_name',
    'linkedin',
    'telegram',
    'whatsapp',
    'phone',
    'avatar',
    'is_mute',
    'cost_per_hour',
    'currency_id',
    'timezone',
    'minimum_pre_booking_time',
])]
class UserProfile extends Model implements HasMedia
{
    /** @use HasFactory<UserProfileFactory> */
    use HasFactory;

    use InteractsWithMedia;

    public const int PREVIEW_HEIGHT = 300;

    public const int PREVIEW_WIDTH = 300;

    public const string DEFAULT_AVATAR_URL = 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80';

    public const string TEST_AVATAR_URL = 'https://ui-avatars.com/api/?name=%s&background=random&size=256&format=png';

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Currency, $this>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this
            ->addMediaConversion('preview')
            ->nonQueued()
            ->fit(Fit::Contain, self::PREVIEW_HEIGHT, self::PREVIEW_WIDTH);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->withResponsiveImages();
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'is_mute' => 'boolean',
        ];
    }

    /**
     * @return Attribute<non-empty-string, never>
     */
    protected function avatar(): Attribute
    {
        return Attribute::get(fn (): string => $this->getFirstMediaUrl('avatar') !== '' ? $this->getFirstMediaUrl('avatar') : self::DEFAULT_AVATAR_URL);
    }
}
