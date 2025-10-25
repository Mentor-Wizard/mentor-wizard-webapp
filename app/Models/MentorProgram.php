<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\MentorProgramObserver;
use Database\Factories\MentorProgramFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperMentorProgram
 */
#[ObservedBy(MentorProgramObserver::class)]
#[UseFactory(MentorProgramFactory::class)]
class MentorProgram extends Model
{
    /** @use HasFactory<MentorProgramFactory> */
    use HasFactory;

    protected $fillable = [
        'mentor_id',
        'name',
        'slug',
        'description',
        'cost',
        'currency_id',
    ];

    /**
     * @return BelongsTo<User, MentorProgram>
     */
    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    /**
     * @return BelongsTo<Currency, MentorProgram>
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * @return HasMany<MentorProgramBlock, MentorProgram>
     */
    public function mentorProgramBlocks(): HasMany
    {
        return $this->hasMany(MentorProgramBlock::class, 'mentor_program_id');
    }

    /**
     * @return BelongsToMany<MentorProfile, MentorProgram>
     */
    public function mentorProfiles(): BelongsToMany
    {
        return $this->belongsToMany(MentorProfile::class, 'mentor_profile_mentor_program');
    }
}
