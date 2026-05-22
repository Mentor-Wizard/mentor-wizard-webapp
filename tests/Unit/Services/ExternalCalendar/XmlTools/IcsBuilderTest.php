<?php

declare(strict_types=1);

use App\Models\CalendarEvent;
use App\Services\XmlTools\ExternalCalendar\IcsBuilder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;

mutates(IcsBuilder::class);

describe('IcsBuilder', function (): void {
    beforeEach(function (): void {
        $this->builder = new IcsBuilder;
        Date::setTestNow(CarbonImmutable::create(2026, 1, 10, 9, 30, 0, 'UTC'));
    });

    afterEach(function (): void {
        Date::setTestNow();
    });

    describe('build', function (): void {
        it('returns a valid iCalendar structure with VCALENDAR/VEVENT wrappers', function (): void {
            $event = CalendarEvent::factory()->create([
                'title'           => 'Team Sync',
                'description'     => 'Weekly sync',
                'start_date_time' => CarbonImmutable::create(2026, 6, 15, 14, 0, 0, 'UTC'),
                'end_date_time'   => CarbonImmutable::create(2026, 6, 15, 15, 0, 0, 'UTC'),
            ]);

            $ics = $this->builder->build('uid-123', $event);

            expect($ics)->toStartWith("BEGIN:VCALENDAR\r\n")
                ->and($ics)->toEndWith("END:VCALENDAR\r\n")
                ->and($ics)->toContain("VERSION:2.0\r\n")
                ->and($ics)->toContain("PRODID:-//MentorWizard//EN\r\n")
                ->and($ics)->toContain("BEGIN:VEVENT\r\n")
                ->and($ics)->toContain("END:VEVENT\r\n");
        });

        it('formats UID, SUMMARY, DTSTART, DTEND, and DTSTAMP correctly', function (): void {
            $event = CalendarEvent::factory()->create([
                'title'           => 'Lunch',
                'description'     => null,
                'start_date_time' => CarbonImmutable::create(2026, 6, 15, 14, 0, 0, 'UTC'),
                'end_date_time'   => CarbonImmutable::create(2026, 6, 15, 15, 0, 0, 'UTC'),
            ]);

            $ics = $this->builder->build('uid-abc', $event);

            expect($ics)->toContain("UID:uid-abc\r\n")
                ->and($ics)->toContain("SUMMARY:Lunch\r\n")
                ->and($ics)->toContain("DTSTART:20260615T140000Z\r\n")
                ->and($ics)->toContain("DTEND:20260615T150000Z\r\n")
                ->and($ics)->toContain("DTSTAMP:20260110T093000Z\r\n");
        });

        it('omits DESCRIPTION line when description is null', function (): void {
            $event = CalendarEvent::factory()->create([
                'title'           => 'Lunch',
                'description'     => null,
                'start_date_time' => CarbonImmutable::create(2026, 6, 15, 14, 0, 0, 'UTC'),
                'end_date_time'   => CarbonImmutable::create(2026, 6, 15, 15, 0, 0, 'UTC'),
            ]);

            $ics = $this->builder->build('uid-1', $event);

            expect($ics)->not->toContain('DESCRIPTION:');
        });

        it('includes DESCRIPTION line when description is present', function (): void {
            $event = CalendarEvent::factory()->create([
                'title'           => 'Meeting',
                'description'     => 'Simple description',
                'start_date_time' => CarbonImmutable::create(2026, 6, 15, 14, 0, 0, 'UTC'),
                'end_date_time'   => CarbonImmutable::create(2026, 6, 15, 15, 0, 0, 'UTC'),
            ]);

            $ics = $this->builder->build('uid-1', $event);

            expect($ics)->toContain("DESCRIPTION:Simple description\r\n");
        });

        it('escapes newlines in description as literal backslash n', function (): void {
            $event = CalendarEvent::factory()->create([
                'title'           => 'Meeting',
                'description'     => "Line 1\nLine 2\r\nLine 3\rLine 4",
                'start_date_time' => CarbonImmutable::create(2026, 6, 15, 14, 0, 0, 'UTC'),
                'end_date_time'   => CarbonImmutable::create(2026, 6, 15, 15, 0, 0, 'UTC'),
            ]);

            $ics = $this->builder->build('uid-1', $event);

            expect($ics)->toContain('DESCRIPTION:Line 1\\nLine 2\\nLine 3\\nLine 4'."\r\n")
                ->and($ics)->not->toContain("DESCRIPTION:Line 1\n");
        });

        it('uses the configured app timezone for DTSTART and DTEND', function (): void {
            config(['app.timezone' => 'Europe/Kyiv']);

            $event = CalendarEvent::factory()->create([
                'title'           => 'Kyiv Event',
                'description'     => null,
                'start_date_time' => CarbonImmutable::create(2026, 6, 15, 14, 0, 0, 'UTC'),
                'end_date_time'   => CarbonImmutable::create(2026, 6, 15, 15, 0, 0, 'UTC'),
            ]);

            $ics = $this->builder->build('uid-tz', $event);

            // Kyiv is UTC+3 in June (EEST)
            expect($ics)->toContain("DTSTART:20260615T170000Z\r\n")
                ->and($ics)->toContain("DTEND:20260615T180000Z\r\n");

            config(['app.timezone' => 'UTC']);
        });

        it('uses CRLF line endings throughout', function (): void {
            $event = CalendarEvent::factory()->create([
                'title'           => 'Meeting',
                'description'     => 'With description',
                'start_date_time' => CarbonImmutable::create(2026, 6, 15, 14, 0, 0, 'UTC'),
                'end_date_time'   => CarbonImmutable::create(2026, 6, 15, 15, 0, 0, 'UTC'),
            ]);

            $ics = $this->builder->build('uid-1', $event);

            // Every linebreak should be a CRLF
            expect(mb_substr_count($ics, "\r\n"))->toBe(mb_substr_count($ics, "\n"));
        });
    });
});
