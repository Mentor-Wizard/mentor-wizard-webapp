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
 * @mixin IdeHelperMentorProfile
 */
class MentorProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'rate',
        'currency_id',
        'experience_started_at',
    ];

    protected $visible = [
        'id',
        'title',
        'description',
        'rate',
        'currency_id',
        'experience_started_at',
    ];
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currency(): ?BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function mentorTags()
    {
        return $this->belongsToMany(MentorTag::class, 'mentor_profile_mentor_tag');
    }

    public function languages(): Attribute
    {
        return Attribute::get(function () {
            return $this->mentorTags()->where('type', MentorTag::LANGUAGE)->get();
        });
    }

    public function stacks(): Attribute
    {
        return Attribute::get(function () {
            return $this->mentorTags()->where('type', MentorTag::STACK)->get();
        });
    }
}
