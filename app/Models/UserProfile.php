<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @mixin IdeHelperUserProfile
 */
class UserProfile extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    public const int PREVIEW_HEIGHT = 300;

    public const int PREVIEW_WIDTH = 300;

    public const string DEFAULT_AVATAR = 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80';

    protected $fillable = [
        'user_id',
        'name',
        'last_name',
        'linkedin',
        'telegram',
        'whatsapp',
        'phone',
        'description',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this
            ->addMediaConversion('preview')
            ->fit(Fit::Contain, self::PREVIEW_HEIGHT, self::PREVIEW_WIDTH)
            ->nonQueued();
    }
}
