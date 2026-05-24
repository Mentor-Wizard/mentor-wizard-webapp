<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Database\Factories\MentorProgramBlockProgressFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * @mixin IdeHelperMentorProgramBlockProgress
 */
#[UseFactory(MentorProgramBlockProgressFactory::class)]
#[Fillable([
    'mentor_program_block_id',
    'menti_id',
    'is_completed',
])]
#[Table(name: 'mentor_program_block_progresses')]
class MentorProgramBlockProgress extends Model
{
    /** @use HasFactory<MentorProgramBlockProgressFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<MentorProgramBlock, $this>
     */
    public function mentorProgramBlocks(): BelongsTo
    {
        return $this->belongsTo(MentorProgramBlock::class, 'mentor_program_block_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function menti(): BelongsTo
    {
        return $this->belongsTo(User::class, 'menti_id');
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'mentor_program_block_id' => 'int',
            'menti_id'                => 'int',
            'is_completed'            => 'boolean',
        ];
    }
}
