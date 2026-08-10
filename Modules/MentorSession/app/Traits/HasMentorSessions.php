<?php

declare(strict_types=1);

namespace Modules\MentorSession\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\MentorSession\Models\MentorSession;

/**
 * @phpstan-require-extends Model
 */
trait HasMentorSessions
{
    /**
     * @return HasMany<MentorSession, $this>
     */
    public function mentorSessions(): HasMany
    {
        return $this->hasMany(MentorSession::class, 'mentor_id');
    }

    /**
     * @return HasMany<MentorSession, $this>
     */
    public function mentiSessions(): HasMany
    {
        return $this->hasMany(MentorSession::class, 'menti_id');
    }
}
