<?php

declare(strict_types=1);

namespace App\Actions\Pages\Profile;

use App\Enums\CalendarProviderEnum;
use App\Enums\CalendarSyncStatusEnum;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;

class ExternalCalendarSettingsPage
{
    use AsController;

    public function handle(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        /** @var array<string, UserCalendarIntegration> $integrations */
        $integrations = UserCalendarIntegration::query()
            ->where('user_id', $user->getKey())
            ->get()
            ->keyBy(fn (UserCalendarIntegration $i): string => $i->provider->value)
            ->all();

        $calendarIntegrations = [];

        foreach (CalendarProviderEnum::cases() as $provider) {
            $integration = $integrations[$provider->value] ?? null;

            $calendarIntegrations[] = [
                'key'                  => $provider->value,
                'connected'            => $integration !== null,
                'needs_reauth'         => $integration !== null && $integration->needs_reauth,
                'sync_status'          => $integration?->sync_status->value ?? CalendarSyncStatusEnum::Disconnected->value,
                'calendar_id'          => $integration?->calendar_id,
                'calendar_name'        => $integration?->calendar_name,
                'last_synced_at'       => $integration?->last_synced_at?->toIso8601String(),
                'last_error_message'   => $integration?->last_error_message,
                'uses_app_credentials' => $provider->usesAppCredentials(),
                'is_cal_dav'           => $provider->isCalDav(),
            ];
        }

        return Inertia::render('Settings/ExternalCalendarPage', [
            'calendarIntegrations' => $calendarIntegrations,
        ]);
    }
}
