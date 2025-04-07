<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MentorProgramBlockFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    protected function casts(): array
    {
        return [
            'mentor_program_id' => 'int',
            'name' => 'string',
            'slug' => 'string',
            'description' => 'string',
        ];
    }

    public function mentorProgramBlockProgress(): HasOne
    {
        return $this->HasOne(MentorProgramBlockProgress::class, 'id');
    }

    public function mentorProgram(): BelongsTo
    {
        return $this->belongsTo(MentorProgram::class, 'id');
    }
}
