<?php

declare(strict_types=1);

use App\Enums\CalendarProviderEnum;
use App\Enums\CalendarSyncStatusEnum;
use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use App\Services\ExternalCalendar\AbstractGoogleExternalCalendarService;
use App\Services\ExternalCalendar\GoogleExternalCalendarService;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;

mutates(AbstractGoogleExternalCalendarService::class);
mutates(GoogleExternalCalendarService::class);

describe('GoogleExternalCalendarService (AbstractGoogleExternalCalendarService via personal-app variant)', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        config(['calendar.encryption_key1' => base64_encode(random_bytes(32))]);

        $this->user = User::factory()->create();
        $this->service = new GoogleExternalCalendarService;
    });

    describe('saveCredentials', function (): void {
        it('creates a new GooglePersonalApp integration with Pending status', function (): void {
            $integration = $this->service->saveCredentials($this->user, 'my-client-id', 'my-client-secret');

            expect($integration->user_id)->toBe($this->user->getKey())
                ->and($integration->provider)->toBe(CalendarProviderEnum::GooglePersonalApp)
                ->and($integration->sync_status)->toBe(CalendarSyncStatusEnum::Pending)
                ->and($integration->needs_reauth)->toBeFalse()
                ->and($integration->access_token)->toBeNull();
        });

        it('updates an existing integration with new credentials', function (): void {
            $existing = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::GooglePersonalApp,
            ]);

            $integration = $this->service->saveCredentials($this->user, 'new-client-id', 'new-secret');

            expect($integration->getKey())->toBe($existing->getKey())
                ->and($integration->sync_status)->toBe(CalendarSyncStatusEnum::Pending);
        });
    });

    describe('buildOAuthUrl', function (): void {
        it('builds a Google authorization URL with the supplied client_id override', function (): void {
            $url = $this->service->buildOAuthUrl('override-client-id', 'oauth-state-token');

            expect($url)
                ->toContain('https://accounts.google.com/o/oauth2/v2/auth')
                ->toContain('client_id=override-client-id')
                ->toContain('response_type=code')
                ->toContain('state=oauth-state-token')
                ->toContain('access_type=offline')
                ->toContain('prompt=consent');
        });

        it('falls back to the integration client_id when no override is provided', function (): void {
            $url = $this->service->buildOAuthUrl(null, 'state-abc');

            expect($url)->toContain('https://accounts.google.com/o/oauth2/v2/auth')
                ->toContain('state=state-abc');
        });
    });

    describe('handleCallback', function (): void {
        it('exchanges the authorization code and updates the integration', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::GooglePersonalApp,
            ]);

            Http::fake([
                'https://oauth2.googleapis.com/*' => Http::response([
                    'access_token'  => 'google-access-token',
                    'refresh_token' => 'google-refresh-token',
                    'expires_in'    => 3600,
                ]),
            ]);

            $result = $this->service->handleCallback($this->user, 'google-auth-code');

            expect($result->getKey())->toBe($integration->getKey())
                ->and($result->sync_status)->toBe(CalendarSyncStatusEnum::Pending)
                ->and($result->token_expires_at)->not->toBeNull();
        });

        it('stores null token_expires_at when expires_in is absent', function (): void {
            UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::GooglePersonalApp,
            ]);

            Http::fake([
                'https://oauth2.googleapis.com/*' => Http::response(['access_token' => 'token-no-expiry']),
            ]);

            $result = $this->service->handleCallback($this->user, 'code');

            expect($result->token_expires_at)->toBeNull();
        });
    });

    describe('selectCalendar', function (): void {
        it('updates the integration with the selected calendar and sets Active status', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::GooglePersonalApp,
            ]);

            $result = $this->service->selectCalendar($this->user, 'google-cal-id', 'My Google Calendar');

            expect($result->getKey())->toBe($integration->getKey())
                ->and($result->calendar_id)->toBe('google-cal-id')
                ->and($result->calendar_name)->toBe('My Google Calendar')
                ->and($result->sync_status)->toBe(CalendarSyncStatusEnum::Active)
                ->and($result->needs_reauth)->toBeFalse()
                ->and($result->last_error_message)->toBeNull();
        });
    });

    describe('fetchCalendars', function (): void {
        it('returns mapped calendar list on success using the summary field', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::GooglePersonalApp,
            ]);

            Http::fake([
                'https://www.googleapis.com/*' => Http::response([
                    'items' => [
                        ['id' => 'cal-1', 'summary' => 'Primary',  'primary' => true],
                        ['id' => 'cal-2', 'summary' => 'Work',     'primary' => false],
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

        it('falls back to the id when summary is absent', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::GooglePersonalApp,
            ]);

            Http::fake([
                'https://www.googleapis.com/*' => Http::response([
                    'items' => [['id' => 'cal-no-summary']],
                ]),
            ]);

            $result = $this->service->fetchCalendars($integration);

            expect($result['calendars'][0]['name'])->toBe('cal-no-summary');
        });

        it('returns the API error message on failure', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::GooglePersonalApp,
            ]);

            Http::fake([
                'https://www.googleapis.com/*' => Http::response(
                    ['error' => ['message' => 'Invalid credentials']],
                    401
                ),
            ]);

            $result = $this->service->fetchCalendars($integration);

            expect($result['success'])->toBeFalse()
                ->and($result['calendars'])->toBe([])
                ->and($result['error'])->toBe('Invalid credentials');
        });

        it('falls back to a default error message when the API gives none', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'  => $this->user->getKey(),
                'provider' => CalendarProviderEnum::GooglePersonalApp,
            ]);

            Http::fake(['https://www.googleapis.com/*' => Http::response([], 500)]);

            $result = $this->service->fetchCalendars($integration);

            expect($result['error'])->toBe('Unable to fetch calendars.');
        });
    });

    describe('fetchEvents', function (): void {
        it('returns mapped events when the token is valid', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'          => $this->user->getKey(),
                'provider'         => CalendarProviderEnum::GooglePersonalApp,
                'calendar_id'      => 'primary',
                'token_expires_at' => now()->addHour(),
            ]);

            Http::fake([
                'https://www.googleapis.com/*' => Http::response([
                    'items' => [
                        [
                            'id'          => 'google-evt-1',
                            'summary'     => 'Sprint Planning',
                            'description' => 'Q2 Sprint kick-off',
                            'start'       => ['dateTime' => '2026-04-18T10:00:00+00:00', 'timeZone' => 'UTC'],
                            'end'         => ['dateTime' => '2026-04-18T11:00:00+00:00', 'timeZone' => 'UTC'],
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
                ->and($events[0]->externalId)->toBe('google-evt-1')
                ->and($events[0]->title)->toBe('Sprint Planning')
                ->and($events[0]->description)->toBe('Q2 Sprint kick-off');
        });

        it('maps all-day events that use the date field instead of dateTime', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'          => $this->user->getKey(),
                'provider'         => CalendarProviderEnum::GooglePersonalApp,
                'token_expires_at' => now()->addHour(),
            ]);

            Http::fake([
                'https://www.googleapis.com/*' => Http::response([
                    'items' => [
                        [
                            'id'      => 'all-day-1',
                            'summary' => 'Company Holiday',
                            'start'   => ['date' => '2026-04-18'],
                            'end'     => ['date' => '2026-04-19'],
                        ],
                    ],
                ]),
            ]);

            $events = $this->service->fetchEvents(
                $integration,
                Date::parse('2026-04-18'),
                Date::parse('2026-04-20'),
            );

            expect($events)->toHaveCount(1)
                ->and($events[0]->title)->toBe('Company Holiday')
                ->and($events[0]->startUtc->format('H:i:s'))->toBe('00:00:00')
                ->and($events[0]->endUtc->format('H:i:s'))->toBe('23:59:59');
        });

        it('uses null description when description field is absent', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'          => $this->user->getKey(),
                'provider'         => CalendarProviderEnum::GooglePersonalApp,
                'token_expires_at' => now()->addHour(),
            ]);

            Http::fake([
                'https://www.googleapis.com/*' => Http::response([
                    'items' => [
                        [
                            'id'      => 'no-desc',
                            'summary' => 'No Desc Event',
                            'start'   => ['dateTime' => '2026-04-18T10:00:00+00:00'],
                            'end'     => ['dateTime' => '2026-04-18T11:00:00+00:00'],
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

        it('throws RuntimeException when the Google API returns an error', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'          => $this->user->getKey(),
                'provider'         => CalendarProviderEnum::GooglePersonalApp,
                'token_expires_at' => now()->addHour(),
            ]);

            Http::fake([
                'https://www.googleapis.com/*' => Http::response(
                    ['error' => ['message' => 'Rate limit exceeded']],
                    429
                ),
            ]);

            expect(fn () => $this->service->fetchEvents(
                $integration,
                Date::parse('2026-04-18'),
                Date::parse('2026-04-19'),
            ))->toThrow(RuntimeException::class, 'Google Calendar fetch events failed: Rate limit exceeded');
        });

        it('throws and marks needs_reauth when the token is expired with no refresh token', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'          => $this->user->getKey(),
                'provider'         => CalendarProviderEnum::GooglePersonalApp,
                'token_expires_at' => now()->subMinute(),
                'refresh_token'    => null,
            ]);

            expect(fn () => $this->service->fetchEvents(
                $integration,
                Date::parse('2026-04-18'),
                Date::parse('2026-04-19'),
            ))->toThrow(RuntimeException::class, 'Google Calendar token expired and no refresh token available.');

            $this->assertDatabaseHas(UserCalendarIntegration::class, [
                'id'           => $integration->getKey(),
                'needs_reauth' => true,
            ]);
        });

        it('throws and marks needs_reauth when the token refresh request fails', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'          => $this->user->getKey(),
                'provider'         => CalendarProviderEnum::GooglePersonalApp,
                'token_expires_at' => now()->subMinute(),
            ]);

            Http::fake([
                'https://oauth2.googleapis.com/*' => Http::response(['error' => 'invalid_client'], 400),
            ]);

            expect(fn () => $this->service->fetchEvents(
                $integration,
                Date::parse('2026-04-18'),
                Date::parse('2026-04-19'),
            ))->toThrow(RuntimeException::class, 'Google Calendar token refresh failed.');

            $this->assertDatabaseHas(UserCalendarIntegration::class, [
                'id'           => $integration->getKey(),
                'needs_reauth' => true,
            ]);
        });

        it('refreshes the token and fetches events when the token is expired but refresh succeeds', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'          => $this->user->getKey(),
                'provider'         => CalendarProviderEnum::GooglePersonalApp,
                'token_expires_at' => now()->subMinute(),
            ]);

            Http::fake([
                'https://oauth2.googleapis.com/*' => Http::response([
                    'access_token' => 'refreshed-google-token',
                    'expires_in'   => 3600,
                ]),
                'https://www.googleapis.com/*' => Http::response(['items' => []]),
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
                'provider'         => CalendarProviderEnum::GooglePersonalApp,
                'calendar_id'      => 'primary',
                'token_expires_at' => now()->addHour(),
            ]);

            Http::fake([
                'https://www.googleapis.com/*' => Http::response(['id' => 'google-created-id']),
            ]);

            $id = $this->service->createEvent($this->calendarEvent, $integration);

            expect($id)->toBe('google-created-id');
        });

        it('throws RuntimeException when event creation fails', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'          => $this->user->getKey(),
                'provider'         => CalendarProviderEnum::GooglePersonalApp,
                'token_expires_at' => now()->addHour(),
            ]);

            Http::fake([
                'https://www.googleapis.com/*' => Http::response(
                    ['error' => ['message' => 'Forbidden']],
                    403
                ),
            ]);

            expect(fn () => $this->service->createEvent($this->calendarEvent, $integration))
                ->toThrow(RuntimeException::class, 'Google Calendar event creation failed: Forbidden');
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
                'provider'         => CalendarProviderEnum::GooglePersonalApp,
                'calendar_id'      => 'primary',
                'token_expires_at' => now()->addHour(),
            ]);

            Http::fake(['https://www.googleapis.com/*' => Http::response([], 200)]);

            $this->service->updateEvent($this->calendarEvent, $integration, 'google-ext-evt-id');

            Http::assertSent(fn ($req): bool => $req->method() === 'PATCH'
                && str_contains((string) $req->url(), 'google-ext-evt-id'));
        });

        it('throws RuntimeException when event update fails', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'          => $this->user->getKey(),
                'provider'         => CalendarProviderEnum::GooglePersonalApp,
                'token_expires_at' => now()->addHour(),
            ]);

            Http::fake([
                'https://www.googleapis.com/*' => Http::response(
                    ['error' => ['message' => 'Gone']],
                    410
                ),
            ]);

            expect(fn () => $this->service->updateEvent($this->calendarEvent, $integration, 'evt-gone'))
                ->toThrow(RuntimeException::class, 'Google Calendar event update failed: Gone');
        });
    });

    describe('deleteEvent', function (): void {
        it('sends a DELETE request and succeeds on 200', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'          => $this->user->getKey(),
                'provider'         => CalendarProviderEnum::GooglePersonalApp,
                'calendar_id'      => 'primary',
                'token_expires_at' => now()->addHour(),
            ]);

            Http::fake(['https://www.googleapis.com/*' => Http::response([], 200)]);

            $this->service->deleteEvent($integration, 'evt-to-delete');

            Http::assertSent(fn ($req): bool => $req->method() === 'DELETE'
                && str_contains((string) $req->url(), 'evt-to-delete'));
        });

        it('does not throw when event is already deleted externally (404)', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'          => $this->user->getKey(),
                'provider'         => CalendarProviderEnum::GooglePersonalApp,
                'token_expires_at' => now()->addHour(),
            ]);

            Http::fake(['https://www.googleapis.com/*' => Http::response([], 404)]);

            $this->service->deleteEvent($integration, 'evt-already-gone');

            expect(true)->toBeTrue();
        });

        it('throws RuntimeException on non-404 failure', function (): void {
            $integration = UserCalendarIntegration::factory()->create([
                'user_id'          => $this->user->getKey(),
                'provider'         => CalendarProviderEnum::GooglePersonalApp,
                'token_expires_at' => now()->addHour(),
            ]);

            Http::fake([
                'https://www.googleapis.com/*' => Http::response(
                    ['error' => ['message' => 'Service unavailable']],
                    503
                ),
            ]);

            expect(fn () => $this->service->deleteEvent($integration, 'evt-error'))
                ->toThrow(RuntimeException::class, 'Google Calendar event deletion failed: Service unavailable');
        });
    });
});
