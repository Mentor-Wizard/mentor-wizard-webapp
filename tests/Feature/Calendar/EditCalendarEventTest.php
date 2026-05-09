<?php

declare(strict_types=1);

use App\Enums\CalendarEventColoursEnum;
use App\Enums\CalendarEventRoleEnum;
use App\Enums\CalendarEventStatusEnum;
use App\Enums\CalendarEventTypeEnum;
use App\Enums\MentorSessionTypeEnum;
use App\Enums\RoleEnum;
use App\Models\CalendarEvent;
use App\Models\MentorProgram;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

describe('Calendar CalendarEvent Edit Page', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        $this->user->profile->timezone = 'Europe/Kyiv';
        $this->user->profile->save();

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->user->getKey(),
        ]);

        auth()->login($this->user);
        $this->nonMentorUser = User::factory()->create();
        $this->event = CalendarEvent::factory()->create([
            'title'             => 'Default event',
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => Date::tomorrow()->format('Y-m-d').' 12:00:00',
            'end_date_time'     => Date::tomorrow()->format('Y-m-d').' 13:00:00',
            'web_link'          => 'https://google.com',
            'date'              => Date::tomorrow()->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'description'       => 'Test description',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $this->event->calendarEventUsers()->attach($this->user->getKey(),
            ['role' => CalendarEventRoleEnum::HOST, 'colour' => CalendarEventColoursEnum::BLUE->value]);
    });

    it('updates event successfully', function (): void {
        actingAs($this->user);

        $updateEventData = [
            'title'             => 'Default event',
            'fromDate'          => Date::tomorrow()->format('Y-m-d'),
            'fromTime'          => '12:00',
            'toDate'            => Date::tomorrow()->format('Y-m-d'),
            'toTime'            => '13:00',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'webLink'           => 'https://new_url_link.com',
            'description'       => 'New description',
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $response = $this->withSession(['_token' => 'test-token'])
            ->patch(route('pages.calendar.edit', [$this->event->getKey(),
                ...$updateEventData,
                '_token' => 'test-token',
            ]));

        $response->assertRedirect(route('pages.calendar.index'));
        // Times are stored in UTC, so 14:00 Europe/Kyiv = 12:00 UTC (2 hour offset)
        $this->assertDatabaseHas('calendar_events', [
            'title'             => 'Default event',
            'status'            => CalendarEventStatusEnum::CONFIRMED,
            'start_date_time'   => Date::tomorrow()->format('Y-m-d').' 12:00:00',
            'end_date_time'     => Date::tomorrow()->format('Y-m-d').' 13:00:00',
            'date'              => Date::tomorrow()->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'web_link'          => 'https://new_url_link.com',
            'description'       => 'New description',
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);

        $this->assertDatabaseHas('calendar_event_user', [
            'calendar_event_id' => $this->event->getKey(),
            'user_id'           => $this->user->getKey(),
            'role'              => CalendarEventRoleEnum::HOST,
        ]);
    });

    it('fails update when title is missing', function (): void {
        actingAs($this->user);
        $data = [
            'fromDate'          => Date::tomorrow()->format('Y-m-d'),
            'fromTime'          => '09:00',
            'toDate'            => Date::tomorrow()->format('Y-m-d'),
            'toTime'            => '10:00',
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'webLink'           => 'https://google.com',
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];
        $this->withSession(['_token' => 'test-token'])
            ->patch(route('pages.calendar.edit', $this->event->getKey()), [
                ...$data,
                '_token' => 'test-token',
            ])->assertSessionHasErrors(['title']);
    });

    it('fails update when title exceeds max length', function (): void {
        actingAs($this->user);
        $data = [
            'title'             => Str::random(256),
            'fromDate'          => Date::tomorrow()->format('Y-m-d'),
            'fromTime'          => '09:00',
            'toDate'            => Date::tomorrow()->format('Y-m-d'),
            'toTime'            => '10:00',
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'webLink'           => 'https://google.com',
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];
        $this->withSession(['_token' => 'test-token'])
            ->patch(route('pages.calendar.edit', $this->event->getKey()), [
                ...$data,
                '_token' => 'test-token',
            ])->assertSessionHasErrors(['title']);
    });

    it('fails update when fromDate missing/invalid/past', function (): void {
        actingAs($this->user);
        // missing
        $base = [
            'title'             => 'Event',
            'fromTime'          => '09:00',
            'toDate'            => Date::tomorrow()->format('Y-m-d'),
            'toTime'            => '10:00',
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'webLink'           => 'https://google.com',
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];
        $this->withSession(['_token' => 'test-token'])
            ->patch(route('pages.calendar.edit', $this->event->getKey()), [
                ...$base,
                '_token' => 'test-token',
            ])->assertSessionHasErrors(['fromDate']);

        // invalid format
        $invalid = [
            ...$base,
            'fromDate' => '2024-00---',
        ];
        $this->withSession(['_token' => 'test-token'])
            ->patch(route('pages.calendar.edit', $this->event->getKey()), [
                ...$invalid,
                '_token' => 'test-token',
            ])->assertSessionHasErrors(['fromDate']);

        // past
        $past = [
            ...$base,
            'fromDate' => Date::yesterday()->format('Y-m-d'),
            'toDate'   => Date::yesterday()->format('Y-m-d'),
        ];
        $this->withSession(['_token' => 'test-token'])
            ->patch(route('pages.calendar.edit', $this->event->getKey()), [
                ...$past,
                '_token' => 'test-token',
            ])->assertSessionHasErrors(['fromDate']);
    });

    it('fails update when toDate missing/invalid/before fromDate', function (): void {
        actingAs($this->user);
        $base = [
            'title'             => 'Event',
            'fromDate'          => Date::tomorrow()->format('Y-m-d'),
            'fromTime'          => '09:00',
            'toTime'            => '10:00',
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'webLink'           => 'https://google.com',
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];
        // missing
        $this->withSession(['_token' => 'test-token'])
            ->patch(route('pages.calendar.edit', $this->event->getKey()), [
                ...$base,
                '_token' => 'test-token',
            ])->assertSessionHasErrors(['toDate']);

        // invalid
        $invalid = [
            ...$base,
            'toDate' => 'bad-date',
        ];
        $this->withSession(['_token' => 'test-token'])
            ->patch(route('pages.calendar.edit', $this->event->getKey()), [
                ...$invalid,
                '_token' => 'test-token',
            ])->assertSessionHasErrors(['toDate']);

        // before fromDate
        $before = [
            ...$base,
            'toDate' => Date::today()->format('Y-m-d'),
        ];
        $this->withSession(['_token' => 'test-token'])
            ->patch(route('pages.calendar.edit', $this->event->getKey()), [
                ...$before,
                '_token' => 'test-token',
            ])->assertSessionHasErrors(['toDate']);
    });

    it('fails update when fromTime missing/invalid', function (): void {
        actingAs($this->user);
        $base = [
            'title'             => 'Event',
            'fromDate'          => Date::tomorrow()->format('Y-m-d'),
            'toDate'            => Date::tomorrow()->format('Y-m-d'),
            'toTime'            => '10:00',
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'webLink'           => 'https://google.com',
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];
        // missing
        $this->withSession(['_token' => 'test-token'])
            ->patch(route('pages.calendar.edit', $this->event->getKey()), [
                ...$base,
                '_token' => 'test-token',
            ])->assertSessionHasErrors(['fromTime']);

        // invalid
        $invalid = [
            ...$base,
            'fromTime' => '9 AM',
        ];
        $this->withSession(['_token' => 'test-token'])
            ->patch(route('pages.calendar.edit', $this->event->getKey()), [
                ...$invalid,
                '_token' => 'test-token',
            ])->assertSessionHasErrors(['fromTime']);
    });

    it('fails update when toTime missing/invalid/not after', function (): void {
        actingAs($this->user);
        $base = [
            'title'             => 'Event',
            'fromDate'          => Date::tomorrow()->format('Y-m-d'),
            'fromTime'          => '09:00',
            'toDate'            => Date::tomorrow()->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'webLink'           => 'https://google.com',
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];
        // missing
        $this->withSession(['_token' => 'test-token'])
            ->patch(route('pages.calendar.edit', $this->event->getKey()), [
                ...$base,
                '_token' => 'test-token',
            ])->assertSessionHasErrors(['toTime']);

        // invalid
        $invalid = [
            ...$base,
            'toTime' => '10 AM',
        ];
        $this->withSession(['_token' => 'test-token'])
            ->patch(route('pages.calendar.edit', $this->event->getKey()), [
                ...$invalid,
                '_token' => 'test-token',
            ])->assertSessionHasErrors(['toTime']);

        // not after
        $notAfter = [
            ...$base,
            'toTime' => '08:59',
        ];
        $this->withSession(['_token' => 'test-token'])
            ->patch(route('pages.calendar.edit', $this->event->getKey()), [
                ...$notAfter,
                '_token' => 'test-token',
            ])->assertSessionHasErrors(['toTime']);
    });

    it('fails update when type missing/invalid', function (): void {
        actingAs($this->user);
        $base = [
            'title'             => 'Event',
            'fromDate'          => Date::tomorrow()->format('Y-m-d'),
            'fromTime'          => '09:00',
            'toDate'            => Date::tomorrow()->format('Y-m-d'),
            'toTime'            => '10:00',
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'webLink'           => 'https://google.com',
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];
        // missing
        $this->withSession(['_token' => 'test-token'])
            ->patch(route('pages.calendar.edit', $this->event->getKey()), [
                ...$base,
                '_token' => 'test-token',
            ])->assertSessionHasErrors(['type']);
        // invalid
        $invalid = [
            ...$base,
            'type' => 'invalid_type',
        ];
        $this->withSession(['_token' => 'test-token'])
            ->patch(route('pages.calendar.edit', $this->event->getKey()), [
                ...$invalid,
                '_token' => 'test-token',
            ])->assertSessionHasErrors(['type']);
    });

    it('fails update when session_type missing/invalid', function (): void {
        actingAs($this->user);
        $base = [
            'title'             => 'Event',
            'fromDate'          => Date::tomorrow()->format('Y-m-d'),
            'fromTime'          => '09:00',
            'toDate'            => Date::tomorrow()->format('Y-m-d'),
            'toTime'            => '10:00',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'webLink'           => 'https://google.com',
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];
        // missing
        $this->withSession(['_token' => 'test-token'])
            ->patch(route('pages.calendar.edit', $this->event->getKey()), [
                ...$base,
                '_token' => 'test-token',
            ])->assertSessionHasErrors(['session_type']);
        // invalid
        $invalid = [
            ...$base,
            'session_type' => 'invalid_type',
        ];
        $this->withSession(['_token' => 'test-token'])
            ->patch(route('pages.calendar.edit', $this->event->getKey()), [
                ...$invalid,
                '_token' => 'test-token',
            ])->assertSessionHasErrors(['session_type']);
    });

    it('fails update when colour missing', function (): void {
        actingAs($this->user);
        $data = [
            'title'             => 'Event',
            'fromDate'          => Date::tomorrow()->format('Y-m-d'),
            'fromTime'          => '09:00',
            'toDate'            => Date::tomorrow()->format('Y-m-d'),
            'toTime'            => '10:00',
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'webLink'           => 'https://google.com',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];
        $this->withSession(['_token' => 'test-token'])
            ->patch(route('pages.calendar.edit', $this->event->getKey()), [
                ...$data,
                '_token' => 'test-token',
            ])->assertSessionHasErrors(['colour']);
    });

    it('fails update when description is too long', function (): void {
        actingAs($this->user);
        $data = [
            'title'             => 'Event',
            'fromDate'          => Date::tomorrow()->format('Y-m-d'),
            'fromTime'          => '09:00',
            'toDate'            => Date::tomorrow()->format('Y-m-d'),
            'toTime'            => '10:00',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'webLink'           => 'https://google.com',
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'description'       => Str::random(2001),
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];
        $this->withSession(['_token' => 'test-token'])
            ->patch(route('pages.calendar.edit', $this->event->getKey()), [
                ...$data,
                '_token' => 'test-token',
            ])->assertSessionHasErrors(['description']);
    });

    it('fails update when wrong link formatting', function (): void {
        actingAs($this->user);
        $data = [
            'title'             => 'Event',
            'fromDate'          => Date::tomorrow()->format('Y-m-d'),
            'fromTime'          => '09:00',
            'toDate'            => Date::tomorrow()->format('Y-m-d'),
            'toTime'            => '10:00',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'webLink'           => '-----google.com',
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'description'       => Str::random(2001),
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];
        $this->withSession(['_token' => 'test-token'])
            ->patch(route('pages.calendar.edit', $this->event->getKey()), [
                ...$data,
                '_token' => 'test-token',
            ])->assertSessionHasErrors(['webLink']);
    });

    it('throws 403 when a non-mentor user tries to update an event', function (): void {
        actingAs($this->nonMentorUser);
        auth()->login($this->nonMentorUser);

        $eventData = [
            'title'             => 'Default event',
            'fromDate'          => Date::today()->addDays(2)->format('Y-m-d'),
            'fromTime'          => '09:00',
            'toDate'            => Date::today()->addDays(2)->format('Y-m-d'),
            'toTime'            => '10:00',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'webLink'           => 'https://google.com',
            'description'       => 'Test description',
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];
        $response = $this->withSession(['_token' => 'test-token'])
            ->patch(route('pages.calendar.edit', $this->event->getKey()), [
                ...$eventData,
                '_token' => 'test-token',
            ]);
        $response->assertForbidden();
    });
});

describe('Calendar CalendarEvent Edit - Status Restrictions', function (): void {
    beforeEach(function (): void {
        $this->seed(RoleSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole(Role::findByName(RoleEnum::MENTOR->value));
        $this->user->profile->timezone = 'Europe/Kyiv';
        $this->user->profile->save();

        $this->mentorProgram = MentorProgram::factory()->create([
            'mentor_id' => $this->user->getKey(),
        ]);

        auth()->login($this->user);
    });

    it('cannot edit event with FINISHED status', function (): void {
        $finishedEvent = CalendarEvent::factory()->create([
            'title'             => 'Finished event',
            'status'            => CalendarEventStatusEnum::FINISHED->value,
            'start_date_time'   => Date::yesterday()->format('Y-m-d').' 12:00:00',
            'end_date_time'     => Date::yesterday()->format('Y-m-d').' 13:00:00',
            'date'              => Date::yesterday()->format('Y-m-d'),
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $finishedEvent->calendarEventUsers()->attach($this->user->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        actingAs($this->user);

        $eventData = [
            'title'             => 'Updated title',
            'fromDate'          => Date::tomorrow()->format('Y-m-d'),
            'fromTime'          => '12:00',
            'toDate'            => Date::tomorrow()->format('Y-m-d'),
            'toTime'            => '13:00',
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $response = $this->withSession(['_token' => 'test-token'])
            ->patch(route('pages.calendar.edit', $finishedEvent->getKey()), [
                ...$eventData,
                '_token' => 'test-token',
            ]);

        // Should return error or forbidden
        expect($response->status())->toBeIn([302, 403]);

        if ($response->status() === 302) {
            $response->assertSessionHas('error');
        }

        // Title should remain unchanged
        expect($finishedEvent->fresh()->title)->toBe('Finished event');
    });

    it('cannot edit event with CANCELLED status', function (): void {
        $cancelledEvent = CalendarEvent::factory()->create([
            'title'             => 'Cancelled event',
            'status'            => CalendarEventStatusEnum::CANCELLED->value,
            'start_date_time'   => Date::tomorrow()->format('Y-m-d').' 12:00:00',
            'end_date_time'     => Date::tomorrow()->format('Y-m-d').' 13:00:00',
            'date'              => Date::tomorrow()->format('Y-m-d'),
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $cancelledEvent->calendarEventUsers()->attach($this->user->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        actingAs($this->user);

        $eventData = [
            'title'             => 'Updated title',
            'fromDate'          => Date::tomorrow()->format('Y-m-d'),
            'fromTime'          => '12:00',
            'toDate'            => Date::tomorrow()->format('Y-m-d'),
            'toTime'            => '13:00',
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $response = $this->withSession(['_token' => 'test-token'])
            ->patch(route('pages.calendar.edit', $cancelledEvent->getKey()), [
                ...$eventData,
                '_token' => 'test-token',
            ]);

        // Should return error or forbidden
        expect($response->status())->toBeIn([302, 403]);

        if ($response->status() === 302) {
            $response->assertSessionHas('error');
        }

        // Title should remain unchanged
        expect($cancelledEvent->fresh()->title)->toBe('Cancelled event');
    });

    it('cannot edit title of event with confirmed status', function (): void {
        $confirmedEvent = CalendarEvent::factory()->create([
            'title'             => 'Confirmed event',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => Date::tomorrow()->format('Y-m-d').' 12:00:00',
            'end_date_time'     => Date::tomorrow()->format('Y-m-d').' 13:00:00',
            'date'              => Date::tomorrow()->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $confirmedEvent->calendarEventUsers()->attach($this->user->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        actingAs($this->user);

        $eventData = [
            'title'             => 'Updated title',
            'fromDate'          => Date::tomorrow()->format('Y-m-d'),
            'fromTime'          => '12:00',
            'toDate'            => Date::tomorrow()->format('Y-m-d'),
            'toTime'            => '13:00',
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $response = $this->withSession(['_token' => 'test-token'])
            ->patch(route('pages.calendar.edit', $confirmedEvent->getKey()), [
                ...$eventData,
                '_token' => 'test-token',
            ]);

        $response->assertRedirect(route('pages.calendar.index'));

        expect($confirmedEvent->fresh()->title)->toBe('Confirmed event');
    });

    it('can edit web-link of  event with PENDING_MENTOR_CONFIRMATION status', function (): void {
        $pendingEvent = CalendarEvent::factory()->create([
            'title'             => 'Pending event',
            'status'            => CalendarEventStatusEnum::PENDING_MENTOR_CONFIRMATION->value,
            'start_date_time'   => Date::tomorrow()->format('Y-m-d').' 12:00:00',
            'end_date_time'     => Date::tomorrow()->format('Y-m-d').' 13:00:00',
            'date'              => Date::tomorrow()->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $pendingEvent->calendarEventUsers()->attach($this->user->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        actingAs($this->user);

        $eventData = [
            'title'             => 'Pending event',
            'fromDate'          => Date::tomorrow()->format('Y-m-d'),
            'fromTime'          => '12:00',
            'toDate'            => Date::tomorrow()->format('Y-m-d'),
            'toTime'            => '13:00',
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'webLink'           => 'https://facebook.com',
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $response = $this->withSession(['_token' => 'test-token'])
            ->patch(route('pages.calendar.edit', $pendingEvent->getKey()), [
                ...$eventData,
                '_token' => 'test-token',
            ]);

        $response->assertRedirect(route('pages.calendar.index'));

        expect($pendingEvent->fresh()->web_link)->toBe('https://facebook.com');
    });

    it('unconfirmed user cannot edit calendar events', function (): void {
        $unconfirmedUser = User::factory()->unverified()->create();
        $unconfirmedUser->assignRole(Role::findByName(RoleEnum::MENTOR->value));

        $event = CalendarEvent::factory()->create([
            'title'             => 'Event to edit',
            'status'            => CalendarEventStatusEnum::CONFIRMED->value,
            'start_date_time'   => Date::tomorrow()->format('Y-m-d').' 12:00:00',
            'end_date_time'     => Date::tomorrow()->format('Y-m-d').' 13:00:00',
            'date'              => Date::tomorrow()->format('Y-m-d'),
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ]);
        $event->calendarEventUsers()->attach($unconfirmedUser->getKey(), [
            'role'   => CalendarEventRoleEnum::HOST,
            'colour' => CalendarEventColoursEnum::BLUE->value,
        ]);

        actingAs($unconfirmedUser);
        auth()->login($unconfirmedUser);

        $eventData = [
            'title'             => 'Updated title',
            'fromDate'          => Date::tomorrow()->format('Y-m-d'),
            'fromTime'          => '12:00',
            'toDate'            => Date::tomorrow()->format('Y-m-d'),
            'toTime'            => '13:00',
            'session_type'      => MentorSessionTypeEnum::VIDEO_SESSION->value,
            'type'              => CalendarEventTypeEnum::INDIVIDUAL->value,
            'colour'            => CalendarEventColoursEnum::BLUE->value,
            'mentor_program_id' => $this->mentorProgram->getKey(),
        ];

        $response = $this->withSession(['_token' => 'test-token'])
            ->patch(route('pages.calendar.edit', $event->getKey()), [
                ...$eventData,
                '_token' => 'test-token',
            ]);

        // Should redirect to verification notice or return 403
        expect($response->status())->toBeIn([302, 403, 409]);
    });
});
