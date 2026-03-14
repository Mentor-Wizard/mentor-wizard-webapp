# Calendar Booking System Documentation

## Overview

The calendar booking system allows mentees to book sessions with mentors based
on available time slots. The system handles timezone conversions, schedule
exclusions, and session duration management.

## Key Concepts

### 4.1 Future Dates Only

Events can only be created for future dates. The system enforces this through
validation rules:

- `fromDate` must be today or a future date
- `toDate` must be on or after `fromDate`
- The maximum booking window is defined by
  `CalendarEvent::MAXIMUM_NUMBER_OF_MONTHS_EVENT_CAN_BE_SET` (6 months)

### 4.2 MentorProgram Connection

Every calendar event associated with mentor sessions requires a valid
`mentor_program_id`:

- The `mentor_program_id` links the event to a specific mentorship program
- Only the mentor who owns the program can create events for it
- Events without a `mentor_program_id` are treated as personal calendar events

### 4.3 Event Status Flow

Calendar events follow a specific status workflow:

```
PENDING_MENTOR_CONFIRMATION -> CONFIRMED -> FINISHED
                           \-> CANCELLED
```

**Status Definitions:**

| Status                        | Description                                           |
| ----------------------------- | ----------------------------------------------------- |
| `PENDING_MENTOR_CONFIRMATION` | Event created by mentee, awaiting mentor confirmation |
| `PENDING_PAYMENT`             | Event confirmed but payment pending                   |
| `CONFIRMED`                   | Event confirmed by all parties                        |
| `FINISHED`                    | Event has been completed                              |
| `CANCELLED`                   | Event was cancelled                                   |

**Status Transitions:**

1. When a mentee books a slot, the event is created with
   `PENDING_MENTOR_CONFIRMATION` status
2. When the mentor confirms, status changes to `CONFIRMED`
3. A `MentorSession` is automatically created when status changes to `CONFIRMED`
4. Completed events are marked as `FINISHED`
5. Either party can cancel, changing status to `CANCELLED`

**Restrictions:**

- `FINISHED` events cannot be edited or deleted
- `CANCELLED` events cannot be edited or deleted
- Only `CONFIRMED` and `PENDING_MENTOR_CONFIRMATION` events can be modified

### 4.4 Session Duration

Each mentor program has a configurable `session_duration` setting (in minutes):

- Default session duration options: 30, 45, 60, 90, 120 minutes
- Available slots are automatically split based on session duration
- The `SplitSlotsPerSessionDuration` service handles slot splitting

**How it works:**

1. Available time blocks are calculated from mentor's schedule
2. Existing events are excluded from available time
3. Remaining time is split into slots matching the program's session duration

### 4.5 Minimum Pre-booking Time

The `minimum_pre_booking_time` setting from mentor profile controls how far in
advance bookings can be made:

- Stored in minutes on the mentor's profile
- Default: 0 (no minimum)
- Example: Setting to 1440 (24 hours) means mentees must book at least 1 day in
  advance

## Core Services

### BookingCalendarEventsService

Main service for generating available booking slots:

```php
$service = new BookingCalendarEventsService(
    $selectedDate,      // Carbon date to display
    $mentor,            // User model (mentor)
    $timezone,          // Client timezone
    $excludeSchedule,   // Whether to apply mentor's schedule
    $mentorProgram      // MentorProgram model
);

$result = $service->getFormattedMonthAvailableSlots();
// Returns: ['calendarSlots' => [...], 'hasEventsBefore' => bool, 'hasEventsAfter' => bool]
```

### AvailableCalendarEventsSlotsService

Calculates raw available time slots:

- Excludes time occupied by existing events
- Handles multi-day events
- Respects timezone conversions

### ExcludeUserScheduleSchemeService

Filters available slots based on mentor's working schedule:

- Supports multiple working periods per day
- Handles day-off dates
- Performs timezone conversions for schedule matching

### SplitSlotsPerSessionDuration

Splits available time blocks into bookable slots:

- Groups slots by date
- Handles slots spanning midnight
- Respects session duration from mentor program

## CalendarEventObserver

Automatically creates `MentorSession` records when events are confirmed:

**Triggers when:**

- Event status changes from any status TO `CONFIRMED`
- Event has a valid `mentor_program_id`
- Event has both HOST and PARTICIPANT users attached

**Creates MentorSession with:**

- `mentor_id`: From user with HOST role
- `menti_id`: From user with PARTICIPANT role
- `date`: From event's `start_date_time`
- `mentor_program_id`: From event's `mentor_program_id`

## Timezone Handling

The system stores all times in UTC and converts for display:

1. Events are stored with UTC timestamps in `start_date_time` and
   `end_date_time`
2. User's timezone preference is stored in their profile
3. Available slots are calculated and returned in the requested timezone
4. Schedule times are stored as local times and converted when checking
   availability

## Concurrent Booking Prevention

The system prevents double-booking:

1. When confirming an event, the system checks for overlapping confirmed events
2. Partial overlaps are blocked (event A: 14:00-15:00, event B: 14:30-15:30)
3. Adjacent slots are allowed (event A ends at 15:00, event B starts at 15:00)
4. Events completely inside another are blocked

## Database Schema

### calendar_events

| Column            | Type     | Description                      |
| ----------------- | -------- | -------------------------------- |
| id                | bigint   | Primary key                      |
| title             | string   | Event title                      |
| status            | string   | Event status (enum)              |
| start_date_time   | datetime | Start time (UTC)                 |
| end_date_time     | datetime | End time (UTC)                   |
| date              | date     | Event date                       |
| type              | string   | Event type (individual, group)   |
| web_link          | string   | Meeting link (optional)          |
| description       | text     | Event description (optional)     |
| mentor_program_id | bigint   | FK to mentor_programs (nullable) |
| mentor_session_id | bigint   | FK to mentor_sessions (nullable) |

### calendar_event_user (pivot)

| Column            | Type     | Description                           |
| ----------------- | -------- | ------------------------------------- |
| calendar_event_id | bigint   | FK to calendar_events                 |
| user_id           | bigint   | FK to users                           |
| role              | string   | User's role (Host, Participant, etc.) |
| colour            | string   | Display colour                        |
| confirmed_at      | datetime | When user confirmed                   |

### user_schedules

| Column       | Type   | Description               |
| ------------ | ------ | ------------------------- |
| user_id      | bigint | FK to users               |
| day_of_week  | int    | 1-7 (Monday-Sunday)       |
| start_time   | time   | Working hours start       |
| end_time     | time   | Working hours end         |
| type         | string | WORKING_DAY or DAY_OFF    |
| day_off_date | date   | Specific date for day off |

## Testing

Run calendar-related tests:

```bash
# Run all calendar tests
php artisan test --filter=Calendar

# Run specific service tests
php artisan test tests/Unit/Services/Calendar/

# Run mutation tests
./vendor/bin/pest --mutate --class=BookingCalendarEventsService
./vendor/bin/pest --mutate --class=CalendarEventObserver
```
