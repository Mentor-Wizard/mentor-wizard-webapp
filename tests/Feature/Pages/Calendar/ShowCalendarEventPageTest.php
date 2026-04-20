<?php

declare(strict_types=1);

use App\Enums\CalendarEventColoursEnum;
use App\Enums\CalendarEventRoleEnum;
use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Enums\CalendarProviderEnum;
use App\Enums\CalendarSyncStatusEnum;
use App\Enums\ExternalCalendarEventSyncStatusEnum;
use App\Enums\MentorSessionTypeEnum;
use App\Enums\RoleEnum;
use App\Models\CalendarEvent;
use App\Models\ExternalCalendarEvent;
use App\Models\ExternalCalendarEventLog;
use App\Models\MentorProgram;
use App\Models\User;
use App\Models\UserCalendarIntegration;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

describe('Calendar Pages - ShowCalendarEvent', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);

        $this->host = User::factory()->create();
        $this->host->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->host->getKey(),
        ]);
        $this->participant = User::factory()->create();
        $this->stranger = User::factory()->create();

        $this->event = CalendarEvent::factory()->create([
            'title'             => 'Event to Show',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => Date::tomorrow()->format('Y-m-d').' 12:00:00',
            'end_date_time'     => Date::tomorrow()->format('Y-m-d').' 13:00:00',
            'date'              => Date::tomorrow()->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'description'       => 'Details',
            'mentor_program_id' => $mentorProgram->getKey(),
        ]);

        // Attach relations
        $this->event->calendarEventUsers()->attach($this->host->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);
        $this->event->calendarEventUsers()->attach($this->participant->getKey(), [
            'role'   => CalendarEventRoleEnum::PARTICIPANT->value,
            'colour' => CalendarEventColoursEnum::GREEN->value,
        ]);
    });

    it('redirects guests to login', function (): void {
        $this->get(route('pages.calendar.show', $this->event->getKey()))
            ->assertRedirect(route('login'));
    });

    it('allows host to view with edit permissions', function (): void {
        $response = $this->actingAs($this->host)
            ->get(route('pages.calendar.show', $this->event->getKey()));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Calendar/ShowEditCalendarEvent')
            ->has('availableColours')
            ->where('permissions', 'edit')
            ->has('calendarEvent')
        );
    });

    it('allows participant to view with view permissions', function (): void {
        $response = $this->actingAs($this->participant)
            ->get(route('pages.calendar.show', $this->event->getKey()));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertInertia(fn (Assert $page): AssertableJson => $page
            ->component('Calendar/ShowEditCalendarEvent')
            ->where('permissions', 'view')
            ->has('calendarEvent')
        );
    });

    it('forbids unrelated user by policy', function (): void {
        $this->actingAs($this->stranger)
            ->get(route('pages.calendar.show', $this->event->getKey()))
            ->assertForbidden();
    });
});

describe('Calendar Pages - ShowCalendarEvent deferred externalIntegrations', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        config(['calendar.encryption_key1' => base64_encode(random_bytes(32))]);

        $this->host = User::factory()->create();
        $this->host->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->host->getKey(),
        ]);

        $this->event = CalendarEvent::factory()->create([
            'mentor_program_id' => $mentorProgram->getKey(),
            'start_date_time'   => Date::tomorrow()->format('Y-m-d').' 10:00:00',
            'end_date_time'     => Date::tomorrow()->format('Y-m-d').' 11:00:00',
            'date'              => Date::tomorrow()->format('Y-m-d'),
        ]);

        $this->event->calendarEventUsers()->attach($this->host->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST->value,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);
    });

    /** @return array<string, string> */
    function deferredHeaders(): array
    {
        $manifest = public_path('build/manifest.json');
        $version = file_exists($manifest) ? hash_file('xxh128', $manifest) : null;

        $headers = [
            'X-Inertia'                   => 'true',
            'X-Inertia-Partial-Data'      => 'externalIntegrations',
            'X-Inertia-Partial-Component' => 'Calendar/ShowEditCalendarEvent',
        ];

        if ($version !== null) {
            $headers['X-Inertia-Version'] = $version;
        }

        return $headers;
    }

    it('returns empty array when user has no active calendar integrations', function (): void {
        $this->actingAs($this->host)
            ->withHeaders(deferredHeaders())
            ->getJson(route('pages.calendar.show', $this->event->getKey()))
            ->assertOk()
            ->assertJsonPath('props.externalIntegrations', []);
    });

    it('excludes integrations with non-active sync status', function (): void {
        UserCalendarIntegration::factory()->create([
            'user_id'     => $this->host->getKey(),
            'provider'    => CalendarProviderEnum::Google,
            'sync_status' => CalendarSyncStatusEnum::Disconnected,
        ]);

        $this->actingAs($this->host)
            ->withHeaders(deferredHeaders())
            ->getJson(route('pages.calendar.show', $this->event->getKey()))
            ->assertOk()
            ->assertJsonPath('props.externalIntegrations', []);
    });

    it('excludes other users integrations', function (): void {
        $otherUser = User::factory()->create();
        UserCalendarIntegration::factory()->create([
            'user_id'     => $otherUser->getKey(),
            'provider'    => CalendarProviderEnum::Google,
            'sync_status' => CalendarSyncStatusEnum::Active,
        ]);

        $this->actingAs($this->host)
            ->withHeaders(deferredHeaders())
            ->getJson(route('pages.calendar.show', $this->event->getKey()))
            ->assertOk()
            ->assertJsonPath('props.externalIntegrations', []);
    });

    it('returns integration with null external_event when no external event exists', function (): void {
        UserCalendarIntegration::factory()->create([
            'user_id'     => $this->host->getKey(),
            'provider'    => CalendarProviderEnum::Google,
            'sync_status' => CalendarSyncStatusEnum::Active,
        ]);

        $response = $this->actingAs($this->host)
            ->withHeaders(deferredHeaders())
            ->getJson(route('pages.calendar.show', $this->event->getKey()));

        $response->assertOk();

        $integrations = $response->json('props.externalIntegrations');
        expect($integrations)->toHaveCount(1)
            ->and($integrations[0]['provider'])->toBe(CalendarProviderEnum::Google->value)
            ->and($integrations[0]['provider_label'])->toBe(CalendarProviderEnum::Google->label())
            ->and($integrations[0]['user_id'])->toBe($this->host->getKey())
            ->and($integrations[0]['user_name'])->toBe($this->host->username)
            ->and($integrations[0]['external_event'])->toBeNull();
    });

    it('returns correct integration_id from UserCalendarIntegration', function (): void {
        $integration = UserCalendarIntegration::factory()->create([
            'user_id'     => $this->host->getKey(),
            'provider'    => CalendarProviderEnum::Google,
            'sync_status' => CalendarSyncStatusEnum::Active,
        ]);

        $response = $this->actingAs($this->host)
            ->withHeaders(deferredHeaders())
            ->getJson(route('pages.calendar.show', $this->event->getKey()));

        $response->assertOk();

        $integrations = $response->json('props.externalIntegrations');
        expect($integrations[0]['integration_id'])->toBe($integration->getKey());
    });

    it('returns integration with external event when one exists for this calendar event', function (): void {
        UserCalendarIntegration::factory()->create([
            'user_id'     => $this->host->getKey(),
            'provider'    => CalendarProviderEnum::Google,
            'sync_status' => CalendarSyncStatusEnum::Active,
        ]);

        $externalEvent = ExternalCalendarEvent::factory()->create([
            'calendar_event_id' => $this->event->getKey(),
            'user_id'           => $this->host->getKey(),
            'provider'          => CalendarProviderEnum::Google,
            'sync_status'       => ExternalCalendarEventSyncStatusEnum::Synced,
        ]);

        $response = $this->actingAs($this->host)
            ->withHeaders(deferredHeaders())
            ->getJson(route('pages.calendar.show', $this->event->getKey()));

        $response->assertOk();

        $integrations = $response->json('props.externalIntegrations');
        expect($integrations)->toHaveCount(1)
            ->and($integrations[0]['external_event'])->not->toBeNull()
            ->and($integrations[0]['external_event']['id'])->toBe($externalEvent->getKey())
            ->and($integrations[0]['external_event']['sync_status'])
            ->toBe(ExternalCalendarEventSyncStatusEnum::Synced->value)
            ->and($integrations[0]['external_event']['logs'])->toBe([]);
    });

    it('excludes external events belonging to other calendar events', function (): void {
        UserCalendarIntegration::factory()->create([
            'user_id'     => $this->host->getKey(),
            'provider'    => CalendarProviderEnum::Google,
            'sync_status' => CalendarSyncStatusEnum::Active,
        ]);

        ExternalCalendarEvent::factory()->create([
            'calendar_event_id' => CalendarEvent::factory()->create([
                'mentor_program_id' => MentorProgram::factory()->create([
                    'mentor_id' => $this->host->getKey(),
                ])->getKey(),
            ])->getKey(),
            'user_id'  => $this->host->getKey(),
            'provider' => CalendarProviderEnum::Google,
        ]);

        $response = $this->actingAs($this->host)
            ->withHeaders(deferredHeaders())
            ->getJson(route('pages.calendar.show', $this->event->getKey()));

        $integrations = $response->json('props.externalIntegrations');
        expect($integrations[0]['external_event'])->toBeNull();
    });

    it('excludes external events belonging to other users', function (): void {
        UserCalendarIntegration::factory()->create([
            'user_id'     => $this->host->getKey(),
            'provider'    => CalendarProviderEnum::Google,
            'sync_status' => CalendarSyncStatusEnum::Active,
        ]);

        $otherUser = User::factory()->create();
        ExternalCalendarEvent::factory()->create([
            'calendar_event_id' => $this->event->getKey(),
            'user_id'           => $otherUser->getKey(),
            'provider'          => CalendarProviderEnum::Google,
        ]);

        $response = $this->actingAs($this->host)
            ->withHeaders(deferredHeaders())
            ->getJson(route('pages.calendar.show', $this->event->getKey()));

        $integrations = $response->json('props.externalIntegrations');
        expect($integrations[0]['external_event'])->toBeNull();
    });

    it('returns external event logs with correct structure', function (): void {
        UserCalendarIntegration::factory()->create([
            'user_id'     => $this->host->getKey(),
            'provider'    => CalendarProviderEnum::Google,
            'sync_status' => CalendarSyncStatusEnum::Active,
        ]);

        $externalEvent = ExternalCalendarEvent::factory()->create([
            'calendar_event_id' => $this->event->getKey(),
            'user_id'           => $this->host->getKey(),
            'provider'          => CalendarProviderEnum::Google,
        ]);

        $log = ExternalCalendarEventLog::factory()->error()->create([
            'external_calendar_event_id' => $externalEvent->getKey(),
            'calendar_event_id'          => $this->event->getKey(),
            'user_id'                    => $this->host->getKey(),
            'provider'                   => CalendarProviderEnum::Google,
            'message'                    => 'Sync failed',
        ]);

        $response = $this->actingAs($this->host)
            ->withHeaders(deferredHeaders())
            ->getJson(route('pages.calendar.show', $this->event->getKey()));

        $logs = $response->json('props.externalIntegrations.0.external_event.logs');
        expect($logs)->toHaveCount(1)
            ->and($logs[0]['id'])->toBe($log->getKey())
            ->and($logs[0]['type'])->toBe('error')
            ->and($logs[0]['message'])->toBe('Sync failed')
            ->and($logs[0]['created_at'])->not->toBeNull();
    });

    it('limits external event logs to 20 most recent', function (): void {
        UserCalendarIntegration::factory()->create([
            'user_id'     => $this->host->getKey(),
            'provider'    => CalendarProviderEnum::Google,
            'sync_status' => CalendarSyncStatusEnum::Active,
        ]);

        $externalEvent = ExternalCalendarEvent::factory()->create([
            'calendar_event_id' => $this->event->getKey(),
            'user_id'           => $this->host->getKey(),
            'provider'          => CalendarProviderEnum::Google,
        ]);

        ExternalCalendarEventLog::factory()->count(25)->create([
            'external_calendar_event_id' => $externalEvent->getKey(),
            'calendar_event_id'          => $this->event->getKey(),
            'user_id'                    => $this->host->getKey(),
            'provider'                   => CalendarProviderEnum::Google,
        ]);

        $response = $this->actingAs($this->host)
            ->withHeaders(deferredHeaders())
            ->getJson(route('pages.calendar.show', $this->event->getKey()));

        $logs = $response->json('props.externalIntegrations.0.external_event.logs');
        expect($logs)->toHaveCount(20);
    });

    it('returns null sync_status when external event has no sync status', function (): void {
        UserCalendarIntegration::factory()->create([
            'user_id'     => $this->host->getKey(),
            'provider'    => CalendarProviderEnum::Google,
            'sync_status' => CalendarSyncStatusEnum::Active,
        ]);

        ExternalCalendarEvent::factory()->create([
            'calendar_event_id' => $this->event->getKey(),
            'user_id'           => $this->host->getKey(),
            'provider'          => CalendarProviderEnum::Google,
            'sync_status'       => null,
        ]);

        $response = $this->actingAs($this->host)
            ->withHeaders(deferredHeaders())
            ->getJson(route('pages.calendar.show', $this->event->getKey()));

        $externalEvent = $response->json('props.externalIntegrations.0.external_event');
        expect($externalEvent['sync_status'])->toBeNull();
    });

    it('returns multiple integrations for different providers', function (): void {
        UserCalendarIntegration::factory()->create([
            'user_id'     => $this->host->getKey(),
            'provider'    => CalendarProviderEnum::Google,
            'sync_status' => CalendarSyncStatusEnum::Active,
        ]);
        UserCalendarIntegration::factory()->create([
            'user_id'     => $this->host->getKey(),
            'provider'    => CalendarProviderEnum::Outlook,
            'sync_status' => CalendarSyncStatusEnum::Active,
        ]);

        $response = $this->actingAs($this->host)
            ->withHeaders(deferredHeaders())
            ->getJson(route('pages.calendar.show', $this->event->getKey()));

        $integrations = $response->json('props.externalIntegrations');
        $providers = array_column($integrations, 'provider');
        expect($integrations)->toHaveCount(2)
            ->and($providers)->toContain(CalendarProviderEnum::Google->value)
            ->and($providers)->toContain(CalendarProviderEnum::Outlook->value);
    });
});
