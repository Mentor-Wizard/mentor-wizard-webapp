<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MentorProgramBlockFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Override;

/**
 * @mixin IdeHelperMentorProgramBlock
 */
#[UseFactory(MentorProgramBlockFactory::class)]
class MentorProgramBlock extends Model
{
    /** @use HasFactory<MentorProgramBlockFactory> */
    use HasFactory;

    protected $fillable = [
        'mentor_program_id',
        'name',
        'slug',
        'description',
    ];

    /**
     * @return HasOne<MentorProgramBlockProgress, $this>
     */
    public function mentorProgramBlockProgress(): HasOne
    {
        return $this->hasOne(MentorProgramBlockProgress::class, 'mentor_program_block_id', 'id');
    }

    /**
     * @return BelongsTo<MentorProgram, $this>
     */
    public function mentorProgram(): BelongsTo
    {
        return $this->belongsTo(MentorProgram::class, 'mentor_program_id');
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'mentor_program_id' => 'int',
            'name'              => 'string',
            'slug'              => 'string',
            'description'       => 'string',
        ];
    }
}
