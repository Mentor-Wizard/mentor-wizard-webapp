<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TagEnum;
use Database\Factories\MentorProfileFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Override;

/**
 * @property-read string $avatar URL of the avatar image
 * @property-read Currency|null $currency
 * @property-read EloquentCollection<int, MentorTag> $languages
 * @property-read EloquentCollection<int, MentorTag> $stacks
 * @TODO : Add visible properties after filters and frontend implementation
 * @mixin IdeHelperMentorProfile
 */
#[UseFactory(MentorProfileFactory::class)]
class MentorProfile extends Model
{
    /** @use HasFactory<MentorProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'rate',
        'currency_id',
        'experience_started_at',
    ];

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

    /**
     * @return BelongsToMany<MentorTag, $this>
     */
    public function mentorTags(): BelongsToMany
    {
        return $this->belongsToMany(MentorTag::class, 'mentor_profile_mentor_tag');
    }

    /**
     * @return BelongsToMany<MentorProgram, $this>
     */
    public function mentorPrograms(): BelongsToMany
    {
        return $this->belongsToMany(MentorProgram::class, 'mentor_profile_mentor_program');
    }

    /**
     * @return Attribute<EloquentCollection<int, MentorTag>, never>
     */
    protected function languages(): Attribute
    {
        return Attribute::get(fn () => $this->mentorTags()->where('type', TagEnum::LANGUAGE)->get());
    }

    /**
     * @return Attribute<EloquentCollection<int, MentorTag>, never>
     */
    protected function stacks(): Attribute
    {
        return Attribute::get(fn () => $this->mentorTags()->where('type', TagEnum::STACK)->get());
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'experience_started_at' => 'date',
        ];
    }
}
