<?php

declare(strict_types=1);

namespace Modules\MentorSession\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\MentorSession\Database\Factories\MentorSessionNoteFactory;
use Override;

/**
 * @mixin IdeHelperMentorSessionNote
 */
#[UseFactory(MentorSessionNoteFactory::class)]
#[Fillable([
    'mentor_session_id',
    'notes',
])]
class MentorSessionNote extends Model
{
    /** @use HasFactory<MentorSessionNoteFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<MentorSession, $this>
     */
    public function mentorSession(): BelongsTo
    {
        return $this->belongsTo(MentorSession::class);
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'mentor_session_id' => 'int',
            'notes'             => 'string',
        ];
    }
}
