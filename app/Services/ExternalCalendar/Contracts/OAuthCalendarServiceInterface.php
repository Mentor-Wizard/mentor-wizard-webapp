<?php

declare(strict_types=1);

namespace App\Services\ExternalCalendar\Contracts;

use App\Models\User;
use App\Models\UserCalendarIntegration;

interface OAuthCalendarServiceInterface extends ExternalCalendarServiceInterface
{
    public function buildOAuthUrl(?string $clientId, string $state): string;

    public function handleCallback(User $user, string $code): UserCalendarIntegration;
}
