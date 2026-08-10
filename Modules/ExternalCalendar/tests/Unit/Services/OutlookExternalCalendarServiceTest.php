<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;
use Modules\Calendar\Models\CalendarEvent;
use Modules\ExternalCalendar\Enums\CalendarProviderEnum;
use Modules\ExternalCalendar\Enums\CalendarSyncStatusEnum;
use Modules\ExternalCalendar\Models\UserCalendarIntegration;
use Modules\ExternalCalendar\Services\OutlookExternalCalendarService;
use Modules\MentorProgram\Models\MentorProgram;

mutates(OutlookExternalCalendarService::class);

describe('OutlookExternalCalendarService', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        config([
            'calendar.microsoft_client_id'     => 'test-ms-client-id',
            'calendar.microsoft_client_secret' => 'test-ms-client-secret',
            'calendar.encryption_key1'         => base64_encode(random_bytes(32)),
        ]);

        $this->user = User::factory()->create();
        $this->service = new OutlookExternalCalendarService;
    });

    describe('saveCredentials', function (): void {
        it('creates a new integration with Pending status and clears all tokens', function (): void {
            $integration = $this->service->saveCredentials($this->user, null, null);

            expect($integration->user_id)->toBe($this->user->getKey())
                ->and($integration->provider)->toBe(CalendarProviderEnum::OUTLOOK)
                ->and($integration->sync_status)->toBe(CalendarSyncStatusEnum::PENDING)
                ->and($integration->access_token)->toBeNull()
                ->and($integration->refresh_token)->toBeNull()
                ->and($integration->needs_reauth)->toBeFalse();
        });

        it('updates an existing Outlook integration and resets tokens', function (): void {
            $existing = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::OUTLOOK,
            ]);

            $integration = $this->service->saveCredentials($this->user, null, null);

            expect($integration->getKey())->toBe($existing->getKey())
                ->and($integration->access_token)->toBeNull()
                ->and($integration->sync_status)->toBe(CalendarSyncStatusEnum::PENDING);
        });
    });

    describe('buildOAuthUrl', function (): void {
        it('builds a Microsoft authorization URL with the correct parameters', function (): void {
            $url = $this->service->buildOAuthUrl(null, 'state-token-abc');

            expect($url)
                ->toContain('https://login.microsoftonline.com/common/oauth2/v2.0/authorize')
                ->toContain('client_id=test-ms-client-id')
                ->toContain('response_type=code')
                ->toContain('state=state-token-abc');
        });
    });

    describe('handleCallback', function (): void {
        it('exchanges the authorization code for tokens and updates the integration', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::OUTLOOK,
            ]);

            Http::fake([
                'https://login.microsoftonline.com/*' => Http::response([
                    'access_token'  => 'new-access-token',
                    'refresh_token' => 'new-refresh-token',
                    'expires_in'    => 3600,
                ]),
            ]);

            $result = $this->service->handleCallback($this->user, 'auth-code-xyz');

            expect($result->getKey())->toBe($integration->getKey())
                ->and($result->sync_status)->toBe(CalendarSyncStatusEnum::PENDING)
                ->and($result->token_expires_at)->not->toBeNull();
        });

        it('stores null token_expires_at when expires_in is absent', function (): void {
            UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::OUTLOOK,
            ]);

            Http::fake([
                'https://login.microsoftonline.com/*' => Http::response(['access_token' => 'new-token']),
            ]);

            $result = $this->service->handleCallback($this->user, 'auth-code');

            expect($result->token_expires_at)->toBeNull();
        });
    });

    describe('selectCalendar', function (): void {
        it('updates the integration with calendar info and sets Active status', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::OUTLOOK,
            ]);

            $result = $this->service->selectCalendar($this->user, 'cal-id-123', 'Outlook Primary');

            expect($result->getKey())->toBe($integration->getKey())
                ->and($result->calendar_id)->toBe('cal-id-123')
                ->and($result->calendar_name)->toBe('Outlook Primary')
                ->and($result->sync_status)->toBe(CalendarSyncStatusEnum::ACTIVE)
                ->and($result->needs_reauth)->toBeFalse()
                ->and($result->last_error_message)->toBeNull();
        });
    });

    describe('fetchCalendars', function (): void {
        it('returns mapped calendar list on success', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::OUTLOOK,
            ]);

            Http::fake([
                'https://graph.microsoft.com/*' => Http::response([
                    'value' => [
                        ['id' => 'cal-1', 'name' => 'Primary', 'isDefaultCalendar' => true],
                        ['id' => 'cal-2', 'name' => 'Work',    'isDefaultCalendar' => false],
                    ],
                ]),
            ]);

            $result = $this->service->fetchCalendars($integration);

            expect($result['success'])->toBeTrue()
                ->and($result['error'])->toBeNull()
                ->and($result['calendars'])->toHaveCount(2)
                ->and($result['calendars'][0])->toBe(['id' => 'cal-1', 'name' => 'Primary', 'primary' => true])
                ->and($result['calendars'][1])->toBe(['id' => 'cal-2', 'name' => 'Work',    'primary' => false]);
        });

        it('falls back to the id when the name field is missing', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::OUTLOOK,
            ]);

            Http::fake([
                'https://graph.microsoft.com/*' => Http::response([
                    'value' => [['id' => 'cal-no-name']],
                ]),
            ]);

            $result = $this->service->fetchCalendars($integration);

            expect($result['calendars'][0]['name'])->toBe('cal-no-name');
        });

        it('returns the API error message on failure', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::OUTLOOK,
            ]);

            Http::fake([
                'https://graph.microsoft.com/*' => Http::response(
                    ['error' => ['message' => 'Access denied']],
                    403
                ),
            ]);

            $result = $this->service->fetchCalendars($integration);

            expect($result['success'])->toBeFalse()
                ->and($result['calendars'])->toBeEmpty()
                ->and($result['error'])->toBe('Access denied');
        });

        it('falls back to a default error message when the API gives none', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::OUTLOOK,
            ]);

            Http::fake(['https://graph.microsoft.com/*' => Http::response([], 500)]);

            $result = $this->service->fetchCalendars($integration);

            expect($result['error'])->toBe('Unable to fetch calendars.');
        });
    });

    describe('fetchEvents', function (): void {
        it('returns mapped events when the token is valid', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'          => $this->user->getKey(),
                'provider'         => CalendarProviderEnum::OUTLOOK,
                'calendar_id'      => 'test-cal',
                'token_expires_at' => now()->addHour(),
            ]);

            Http::fake([
                'https://graph.microsoft.com/*' => Http::response([
                    'value' => [
                        [
                            'id'          => 'evt-1',
                            'subject'     => 'Team Meeting',
                            'start'       => ['dateTime' => '2026-04-18T10:00:00', 'timeZone' => 'UTC'],
                            'end'         => ['dateTime' => '2026-04-18T11:00:00', 'timeZone' => 'UTC'],
                            'body'        => ['content' => 'Agenda notes'],
                            'bodyPreview' => 'Agenda notes',
                        ],
                    ],
                ]),
            ]);

            $events = $this->service->fetchEvents(
                $integration,
                Date::parse('2026-04-18'),
                Date::parse('2026-04-19'),
            );

            expect($events)->toHaveCount(1)
                ->and($events[0]->externalId)->toBe('evt-1')
                ->and($events[0]->title)->toBe('Team Meeting')
                ->and($events[0]->description)->toBe('Agenda notes');
        });

        it('maps whitespace-only body content to a null description', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'          => $this->user->getKey(),
                'provider'         => CalendarProviderEnum::OUTLOOK,
                'token_expires_at' => now()->addHour(),
            ]);

            Http::fake([
                'https://graph.microsoft.com/*' => Http::response([
                    'value' => [
                        [
                            'id'          => 'evt-2',
                            'subject'     => 'No Description',
                            'start'       => ['dateTime' => '2026-04-18T10:00:00', 'timeZone' => 'UTC'],
                            'end'         => ['dateTime' => '2026-04-18T11:00:00', 'timeZone' => 'UTC'],
                            'body'        => ['content' => '   '],
                            'bodyPreview' => '',
                        ],
                    ],
                ]),
            ]);

            $events = $this->service->fetchEvents(
                $integration,
                Date::parse('2026-04-18'),
                Date::parse('2026-04-19'),
            );

            expect($events[0]->description)->toBeNull();
        });

        it('throws RuntimeException when the Graph API returns an error', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'          => $this->user->getKey(),
                'provider'         => CalendarProviderEnum::OUTLOOK,
                'token_expires_at' => now()->addHour(),
            ]);

            Http::fake([
                'https://graph.microsoft.com/*' => Http::response(
                    ['error' => ['message' => 'Unauthorized']],
                    401
                ),
            ]);

            expect(fn () => $this->service->fetchEvents(
                $integration,
                Date::parse('2026-04-18'),
                Date::parse('2026-04-19'),
            ))->toThrow(RuntimeException::class, 'Outlook Calendar fetch events failed: Unauthorized');
        });

        it('throws and marks needs_reauth when token is expired with no refresh token', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'          => $this->user->getKey(),
                'provider'         => CalendarProviderEnum::OUTLOOK,
                'token_expires_at' => now()->subMinute(),
                'refresh_token'    => null,
            ]);

            expect(fn () => $this->service->fetchEvents(
                $integration,
                Date::parse('2026-04-18'),
                Date::parse('2026-04-19'),
            ))->toThrow(RuntimeException::class, 'Outlook Calendar token expired and no refresh token available.');

            $this->assertDatabaseHas(UserCalendarIntegration::class, [
                'id'           => $integration->getKey(),
                'needs_reauth' => true,
            ]);
        });

        it('throws and marks needs_reauth when the token refresh request fails', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'          => $this->user->getKey(),
                'provider'         => CalendarProviderEnum::OUTLOOK,
                'token_expires_at' => now()->subMinute(),
            ]);

            Http::fake([
                'https://login.microsoftonline.com/*' => Http::response(['error' => 'invalid_grant'], 400),
            ]);

            expect(fn () => $this->service->fetchEvents(
                $integration,
                Date::parse('2026-04-18'),
                Date::parse('2026-04-19'),
            ))->toThrow(RuntimeException::class, 'Outlook Calendar token refresh failed.');

            $this->assertDatabaseHas(UserCalendarIntegration::class, [
                'id'           => $integration->getKey(),
                'needs_reauth' => true,
            ]);
        });

        it('refreshes the token and fetches events when the token is expired but refresh succeeds', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'          => $this->user->getKey(),
                'provider'         => CalendarProviderEnum::OUTLOOK,
                'token_expires_at' => now()->subMinute(),
            ]);

            Http::fake([
                'https://login.microsoftonline.com/*' => Http::response([
                    'access_token' => 'refreshed-access-token',
                    'expires_in'   => 3600,
                ]),
                'https://graph.microsoft.com/*' => Http::response(['value' => []]),
            ]);

            $events = $this->service->fetchEvents(
                $integration,
                Date::parse('2026-04-18'),
                Date::parse('2026-04-19'),
            );

            expect($events)->toBeArray()->toBeEmpty();
        });
    });

    describe('createEvent', function (): void {
        beforeEach(function (): void {
            $mentorProgram = MentorProgram::factory()->create(['mentor_id' => $this->user->getKey()]);
            $this->calendarEvent = CalendarEvent::factory()->create([
                'mentor_program_id' => $mentorProgram->getKey(),
                'start_date_time'   => Date::tomorrow()->setTime(10, 0),
                'end_date_time'     => Date::tomorrow()->setTime(11, 0),
            ]);
        });

        it('returns the external event id on success', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'          => $this->user->getKey(),
                'provider'         => CalendarProviderEnum::OUTLOOK,
                'calendar_id'      => 'test-cal',
                'token_expires_at' => now()->addHour(),
            ]);

            Http::fake([
                'https://graph.microsoft.com/*' => Http::response(['id' => 'created-evt-id']),
            ]);

            $id = $this->service->createEvent($this->calendarEvent, $integration);

            expect($id)->toBe('created-evt-id');
        });

        it('throws RuntimeException when event creation fails', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'          => $this->user->getKey(),
                'provider'         => CalendarProviderEnum::OUTLOOK,
                'token_expires_at' => now()->addHour(),
            ]);

            Http::fake([
                'https://graph.microsoft.com/*' => Http::response(
                    ['error' => ['message' => 'Quota exceeded']],
                    429
                ),
            ]);

            expect(fn () => $this->service->createEvent($this->calendarEvent, $integration))
                ->toThrow(RuntimeException::class, 'Outlook Calendar event creation failed: Quota exceeded');
        });
    });

    describe('updateEvent', function (): void {
        beforeEach(function (): void {
            $mentorProgram = MentorProgram::factory()->create(['mentor_id' => $this->user->getKey()]);
            $this->calendarEvent = CalendarEvent::factory()->create([
                'mentor_program_id' => $mentorProgram->getKey(),
                'start_date_time'   => Date::tomorrow()->setTime(10, 0),
                'end_date_time'     => Date::tomorrow()->setTime(11, 0),
            ]);
        });

        it('sends a PATCH request to the correct event URL', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'          => $this->user->getKey(),
                'provider'         => CalendarProviderEnum::OUTLOOK,
                'calendar_id'      => 'test-cal',
                'token_expires_at' => now()->addHour(),
            ]);

            Http::fake(['https://graph.microsoft.com/*' => Http::response([], 200)]);

            $this->service->updateEvent($this->calendarEvent, $integration, 'ext-evt-789');

            Http::assertSent(fn ($req): bool => $req->method() === 'PATCH'
                && str_contains((string) $req->url(), 'ext-evt-789'));
        });

        it('throws RuntimeException when event update fails', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'          => $this->user->getKey(),
                'provider'         => CalendarProviderEnum::OUTLOOK,
                'token_expires_at' => now()->addHour(),
            ]);

            Http::fake([
                'https://graph.microsoft.com/*' => Http::response(
                    ['error' => ['message' => 'Not found']],
                    404
                ),
            ]);

            expect(fn () => $this->service->updateEvent($this->calendarEvent, $integration, 'ext-evt-789'))
                ->toThrow(RuntimeException::class, 'Outlook Calendar event update failed: Not found');
        });
    });

    describe('deleteEvent', function (): void {
        it('sends a DELETE request and succeeds on 200', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'          => $this->user->getKey(),
                'provider'         => CalendarProviderEnum::OUTLOOK,
                'calendar_id'      => 'test-cal',
                'token_expires_at' => now()->addHour(),
            ]);

            Http::fake(['https://graph.microsoft.com/*' => Http::response([], 200)]);

            $this->service->deleteEvent($integration, 'ext-evt-to-delete');

            Http::assertSent(fn ($req): bool => $req->method() === 'DELETE'
                && str_contains((string) $req->url(), 'ext-evt-to-delete'));
        });

        it('does not throw when event is already deleted externally (404)', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'          => $this->user->getKey(),
                'provider'         => CalendarProviderEnum::OUTLOOK,
                'token_expires_at' => now()->addHour(),
            ]);

            Http::fake(['https://graph.microsoft.com/*' => Http::response([], 404)]);

            $this->service->deleteEvent($integration, 'evt-already-gone');

            expect(true)->toBeTrue();
        });

        it('throws RuntimeException on non-404 failure', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'          => $this->user->getKey(),
                'provider'         => CalendarProviderEnum::OUTLOOK,
                'token_expires_at' => now()->addHour(),
            ]);

            Http::fake([
                'https://graph.microsoft.com/*' => Http::response(
                    ['error' => ['message' => 'Internal server error']],
                    500
                ),
            ]);

            expect(fn () => $this->service->deleteEvent($integration, 'evt-error'))
                ->toThrow(RuntimeException::class, 'Outlook Calendar event deletion failed: Internal server error');
        });
    });
});
