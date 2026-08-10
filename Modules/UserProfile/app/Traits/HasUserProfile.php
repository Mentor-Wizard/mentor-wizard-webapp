<?php

declare(strict_types=1);

namespace Modules\UserProfile\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\UserProfile\Models\UserProfile;

/**
 * @phpstan-require-extends Model
 */
trait HasUserProfile
{
    /**
     * @return HasOne<UserProfile, $this>
     */
    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }
}
