<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MentorSessionNoteFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperMentorSessionNote
 */
#[UseFactory(MentorSessionNoteFactory::class)]
final class MentorSessionNote extends Model
{
    /** @use HasFactory<MentorSessionNoteFactory> */
    use HasFactory;

    protected $fillable = [
        'mentor_session_id',
        'notes',
    ];

    public function mentorSession(): BelongsTo
    {
        return $this->belongsTo(MentorSession::class);
    }

    protected function casts(): array
    {
        return [
            'mentor_session_id' => 'int',
            'notes'             => 'string',
        ];
    }
}
