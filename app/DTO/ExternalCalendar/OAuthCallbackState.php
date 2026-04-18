<?php

declare(strict_types=1);

namespace App\DTO\ExternalCalendar;

use App\Enums\CalendarProviderEnum;
use App\Models\User;

readonly class OAuthCallbackState
{
    public function __construct(
        public User $user,
        public CalendarProviderEnum $enum,
    ) {}
}
