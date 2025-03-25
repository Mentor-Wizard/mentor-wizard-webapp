<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MentorProgramBlockProgressFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperMentorProgramBlockProgress
 */
#[UseFactory(MentorProgramBlockProgressFactory::class)]
class MentorProgramBlockProgress extends Model
{
    /** @use HasFactory<MentorProgramBlockProgressFactory> */
    use HasFactory;

    protected $table = 'mentor_program_block_progresses';

    protected $fillable = [
        'mentor_program_block_id',
        'menti_id',
        'is_completed',
    ];

    protected function casts(): array
    {
        return [
            'mentor_program_block_id' => 'int',
            'menti_id' => 'int',
            'is_completed' => 'boolean',
        ];
    }

    public function mentor_program_blocks(): BelongsTo
    {
        return $this->belongsTo(MentorProgramBlock::class, 'mentor_program_block_id');
    }

    public function menti(): BelongsTo
    {
        return $this->belongsTo(User::class, 'menti_id');
    }
}
