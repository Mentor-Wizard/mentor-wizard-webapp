<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property-read string $avatar URL of the avatar image
 *
 * @mixin IdeHelperUserProfile
 */
class UserProfile extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    public const int PREVIEW_HEIGHT = 300;

    public const int PREVIEW_WIDTH = 300;

    public const string DEFAULT_AVATAR_URL = 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80';

    public const string TEST_AVATAR_URL = 'https://ui-avatars.com/api/?name=%s&background=random&size=256&format=png';

    protected $fillable = [
        'user_id',
        'name',
        'last_name',
        'title',
        'linkedin',
        'telegram',
        'whatsapp',
        'phone',
        'description',
        'cost_per_hour',
        'currency_id',
    ];

    protected $visible = [
        'id',
        'name',
        'title',
        'last_name',
        'linkedin',
        'telegram',
        'whatsapp',
        'phone',
        'description',
        'avatar',
        'cost_per_hour',
        'currency_id',
    ];

    protected $appends = [
        'avatar',
        'cost_per_hour',
        'currency_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
            ->singleFile();
    }

    protected function avatar(): Attribute
    {
        return Attribute::get(fn (): string => $this->getFirstMediaUrl('avatar') !== '' ? $this->getFirstMediaUrl('avatar') : self::DEFAULT_AVATAR_URL);
    }
}
