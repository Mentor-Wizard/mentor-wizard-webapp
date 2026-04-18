<?php

declare(strict_types=1);

use App\Enums\CalendarProviderEnum;
use App\Enums\CalendarSyncStatusEnum;
use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use App\Services\ExternalCalendar\GoogleAppExternalCalendarService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;

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
                ->and($integration->provider)->toBe(CalendarProviderEnum::Google)
                ->and($integration->sync_status)->toBe(CalendarSyncStatusEnum::Pending)
                ->and($integration->client_id)->toBeNull()
                ->and($integration->client_secret)->toBeNull()
                ->and($integration->access_token)->toBeNull();
        });

        it('updates an existing Google integration and resets tokens', function (): void {
            $existing = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::Google,
            ]);

            $integration = $this->service->saveCredentials($this->user, null, null);

            expect($integration->getKey())->toBe($existing->getKey())
                ->and($integration->access_token)->toBeNull()
                ->and($integration->sync_status)->toBe(CalendarSyncStatusEnum::Pending);
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
                'provider' => CalendarProviderEnum::Google,
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
                ->and($result->sync_status)->toBe(CalendarSyncStatusEnum::Pending);

            Http::assertSent(fn ($req): bool => str_contains((string) $req->body(), 'app-google-client-id'));
        });
    });

    describe('fetchCalendars', function (): void {
        it('fetches calendars successfully using app-level token', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::Google,
            ]);

            Http::fake([
                'https://www.googleapis.com/*' => Http::response([
                    'items' => [
                        ['id' => 'primary', 'summary' => 'Primary',  'primary' => true],
                    ],
                ]),
            ]);

            $result = $this->service->fetchCalendars($integration);

            expect($result['success'])->toBeTrue()
                ->and($result['calendars'])->toHaveCount(1)
                ->and($result['calendars'][0]['name'])->toBe('Primary');
        });
    });

    describe('createEvent', function (): void {
        it('creates an event and uses config credentials for token refresh if needed', function (): void {
            $mentorProgram = MentorProgram::factory()->create(['mentor_id' => $this->user->getKey()]);
            $event = CalendarEvent::factory()->create([
                'mentor_program_id' => $mentorProgram->getKey(),
                'start_date_time'   => Date::tomorrow()->setTime(10, 0),
                'end_date_time'     => Date::tomorrow()->setTime(11, 0),
            ]);

            $integration = UserCalendarIntegration::factory()->create([
                'user_id'          => $this->user->getKey(),
                'provider'         => CalendarProviderEnum::Google,
                'calendar_id'      => 'primary',
                'token_expires_at' => now()->addHour(),
            ]);

            Http::fake([
                'https://www.googleapis.com/*' => Http::response(['id' => 'app-evt-id']),
            ]);

            $id = $this->service->createEvent($event, $integration);

            expect($id)->toBe('app-evt-id');
        });
    });
});
