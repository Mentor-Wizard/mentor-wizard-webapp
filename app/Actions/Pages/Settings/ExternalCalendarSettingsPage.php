<?php

declare(strict_types=1);

namespace App\Actions\Pages\Settings;

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

        $providers = [];

        foreach (CalendarProviderEnum::cases() as $provider) {
            $integration = $integrations[$provider->value] ?? null;

            $providers[] = [
                'key'                => $provider->value,
                'connected'          => $integration !== null,
                'needs_reauth'       => $integration?->needs_reauth ?? false,
                'sync_status'        => $integration?->sync_status->value ?? CalendarSyncStatusEnum::Disconnected->value,
                'calendar_id'        => $integration?->calendar_id,
                'calendar_name'      => $integration?->calendar_name,
                'last_synced_at'     => $integration?->last_synced_at?->toIso8601String(),
                'last_error_message' => $integration?->last_error_message,
            ];
        }

        return Inertia::render('Settings/ExternalCalendarPage', [
            'providers' => $providers,
        ]);
    }
}
