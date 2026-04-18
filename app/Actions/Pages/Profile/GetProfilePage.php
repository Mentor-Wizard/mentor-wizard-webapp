<?php

declare(strict_types=1);

namespace App\Actions\Pages\Profile;

use App\Enums\CalendarProviderEnum;
use App\Enums\CalendarSyncStatusEnum;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use App\Models\UserProfile;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;

class GetProfilePage
{
    use AsController;

    public function handle(): Response
    {
        /** @var User $user */
        $user = auth()->user();

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
                'needs_reauth'         => (bool) ($integration?->needs_reauth),
                'sync_status'          => $integration?->sync_status->value ?? CalendarSyncStatusEnum::Disconnected->value,
                'calendar_id'          => $integration?->calendar_id,
                'calendar_name'        => $integration?->calendar_name,
                'last_synced_at'       => $integration?->last_synced_at?->toIso8601String(),
                'last_error_message'   => $integration?->last_error_message,
                'uses_app_credentials' => $provider->usesAppCredentials(),
                'is_cal_dav'           => $provider->isCalDav(),
            ];
        }

        return Inertia::render('Profile/EditPage', [
            'mustVerifyEmail'      => $user instanceof MustVerifyEmail, // @pest-mutate-ignore @phpstan-ignore instanceof.alwaysTrue
            'status'               => session('status'),
            'avatar'               => $user->profile->avatar ?: UserProfile::DEFAULT_AVATAR_URL,
            'calendarIntegrations' => $calendarIntegrations,
        ]);
    }
}
