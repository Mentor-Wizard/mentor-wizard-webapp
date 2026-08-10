<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Http;
use Modules\ExternalCalendar\Enums\CalendarProviderEnum;
use Modules\ExternalCalendar\Enums\CalendarSyncStatusEnum;
use Modules\ExternalCalendar\Models\UserCalendarIntegration;
use Modules\ExternalCalendar\Services\GoogleAppExternalCalendarService;

mutates(GoogleAppExternalCalendarService::class);

describe('GoogleAppExternalCalendarService (shared app credentials)', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        config([
            'calendar.google_client_id'     => 'app-google-client-id',
            'calendar.google_client_secret' => 'app-google-client-secret',
            'calendar.encryption_key1'      => base64_encode(random_bytes(32)),
        ]);

        $this->user = User::factory()->create();
        $this->service = new GoogleAppExternalCalendarService;
    });

    describe('saveCredentials', function (): void {
        it('creates a Google (app-level) integration that clears client_id and client_secret', function (): void {
            $integration = $this->service->saveCredentials($this->user, null, null);

            expect($integration->user_id)->toBe($this->user->getKey())
                ->and($integration->provider)->toBe(CalendarProviderEnum::GOOGLE)
                ->and($integration->sync_status)->toBe(CalendarSyncStatusEnum::PENDING)
                ->and($integration->client_id)->toBeNull()
                ->and($integration->client_secret)->toBeNull()
                ->and($integration->access_token)->toBeNull();
        });

        it('updates an existing Google integration and resets tokens', function (): void {
            $existing = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::GOOGLE,
            ]);

            $integration = $this->service->saveCredentials($this->user, null, null);

            expect($integration->getKey())->toBe($existing->getKey())
                ->and($integration->access_token)->toBeNull()
                ->and($integration->sync_status)->toBe(CalendarSyncStatusEnum::PENDING);
        });
    });

    describe('buildOAuthUrl', function (): void {
        it('uses config-based credentials instead of per-user credentials', function (): void {
            $url = $this->service->buildOAuthUrl(null, 'state-xyz');

            expect($url)
                ->toContain('https://accounts.google.com/o/oauth2/v2/auth')
                ->toContain('state=state-xyz');
        });
    });

    describe('handleCallback', function (): void {
        it('exchanges code using config credentials and updates the Google integration', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::GOOGLE,
            ]);

            Http::fake([
                'https://oauth2.googleapis.com/*' => Http::response([
                    'access_token'  => 'app-access-token',
                    'refresh_token' => 'app-refresh-token',
                    'expires_in'    => 3600,
                ]),
            ]);

            $result = $this->service->handleCallback($this->user, 'google-auth-code');

            expect($result->getKey())->toBe($integration->getKey())
                ->and($result->sync_status)->toBe(CalendarSyncStatusEnum::PENDING);

            Http::assertSent(fn ($req): bool => str_contains((string) $req->body(), 'app-google-client-id'));
        });
    });

});
