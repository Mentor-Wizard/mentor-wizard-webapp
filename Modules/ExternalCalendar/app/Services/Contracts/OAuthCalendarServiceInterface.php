<?php

declare(strict_types=1);

namespace Modules\ExternalCalendar\Services\Contracts;

use App\Models\User;
use Modules\ExternalCalendar\Models\UserCalendarIntegration;

interface OAuthCalendarServiceInterface extends ExternalCalendarServiceInterface
{
    public function buildOAuthUrl(?string $clientId, string $state): string;

    public function handleCallback(User $user, string $code): UserCalendarIntegration;
}
