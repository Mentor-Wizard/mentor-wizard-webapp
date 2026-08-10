<?php

declare(strict_types=1);

namespace Modules\MentorProgram\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\MentorProgram\Models\MentorProgram;
use Modules\MentorProgram\Models\MentorProgramBlockProgress;

/**
 * @phpstan-require-extends Model
 */
trait HasMentorPrograms
{
    /**
     * @return HasOne<MentorProgramBlockProgress, $this>
     */
    public function mentiProgramProgress(): HasOne
    {
        return $this->hasOne(MentorProgramBlockProgress::class, 'menti_id');
    }

    /**
     * @return HasMany<MentorProgram, $this>
     */
    public function mentorPrograms(): HasMany
    {
        return $this->hasMany(MentorProgram::class, 'mentor_id');
    }
}
