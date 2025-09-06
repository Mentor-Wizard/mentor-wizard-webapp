<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TagEnum;
use Database\Factories\MentorProfileFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property-read string $avatar URL of the avatar image
 *
 * @mixin IdeHelperMentorProfile
 */
#[UseFactory(MentorProfileFactory::class)]
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

    public function mentorTags(): BelongsToMany
    {
        return $this->belongsToMany(MentorTag::class, 'mentor_profile_mentor_tag');
    }

    public function languages(): Attribute
    {
        return Attribute::get(fn () => $this->mentorTags()->where('type', TagEnum::LANGUAGE)->get());
    }

    public function stacks(): Attribute
    {
        return Attribute::get(fn () => $this->mentorTags()->where('type', TagEnum::STACK)->get());
    }

    protected function casts(): array
    {
        return [
            'experience_started_at' => 'date',
        ];
    }
}
