<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read Category|null $parent
 * @property-read EloquentCollection<int, Category> $children
 * @property-read EloquentCollection<int, MentorProfile> $mentorProfiles
 * @property-read EloquentCollection<int, MentorProgram> $mentorPrograms
 *
 * @mixin IdeHelperCategory
 */
#[UseFactory(CategoryFactory::class)]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'parent_id',
    ];

    /**
     * @return BelongsTo<Category, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return BelongsToMany<MentorProfile, $this>
     */
    public function mentorProfiles(): BelongsToMany
    {
        return $this->belongsToMany(MentorProfile::class, 'category_mentor_profile');
    }

    /**
     * @return BelongsToMany<MentorProgram, $this>
     */
    public function mentorPrograms(): BelongsToMany
    {
        return $this->belongsToMany(MentorProgram::class, 'category_mentor_program');
    }

    /**
     * @param  Builder<Category>  $query
     * @return Builder<Category>
     */
    #[Scope]
    protected function roots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }
}
