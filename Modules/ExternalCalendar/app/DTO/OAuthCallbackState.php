<?php

declare(strict_types=1);

namespace Modules\ExternalCalendar\DTO;

use App\Models\User;
use Modules\ExternalCalendar\Enums\CalendarProviderEnum;

readonly class OAuthCallbackState
{
    public function __construct(
        public User $user,
        public CalendarProviderEnum $enum,
    ) {}
}
