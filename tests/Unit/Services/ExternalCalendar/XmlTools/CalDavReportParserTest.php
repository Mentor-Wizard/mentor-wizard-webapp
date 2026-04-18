<?php

declare(strict_types=1);

use App\DTO\ExternalCalendar\ExternalCalendarEventData;
use App\Services\XmlTools\ExternalCalendar\CalDavReportParser;
use Carbon\CarbonImmutable;

mutates(CalDavReportParser::class);

describe('CalDavReportParser', function (): void {
    beforeEach(function (): void {
        $this->caldavRoot = 'https://caldav.example.com';
        $this->parser = new CalDavReportParser($this->caldavRoot);
    });

    describe('getParsedReport', function (): void {
        it('parses a single event with UTC DTSTART/DTEND', function (): void {
            $ics = "BEGIN:VCALENDAR\r\n"
                ."VERSION:2.0\r\n"
                ."BEGIN:VEVENT\r\n"
                ."UID:event-1\r\n"
                ."SUMMARY:Lunch Meeting\r\n"
                ."DESCRIPTION:Catch up with team\r\n"
                ."DTSTART:20260615T140000Z\r\n"
                ."DTEND:20260615T150000Z\r\n"
                ."END:VEVENT\r\n"
                ."END:VCALENDAR\r\n";

            $xml = buildReportXml([
                ['href' => '/calendars/alice/work/event-1.ics', 'ics' => $ics],
            ]);

            $result = $this->parser->getParsedReport($xml);

            expect($result)->toHaveCount(1)
                ->and($result[0])->toBeInstanceOf(ExternalCalendarEventData::class)
                ->and($result[0]->externalId)->toBe('https://caldav.example.com/calendars/alice/work/event-1.ics')
                ->and($result[0]->title)->toBe('Lunch Meeting')
                ->and($result[0]->description)->toBe('Catch up with team')
                ->and($result[0]->providerTimezone)->toBe('UTC')
                ->and($result[0]->startUtc)->toBeInstanceOf(CarbonImmutable::class)
                ->and($result[0]->startUtc->toIso8601String())->toBe(
                    CarbonImmutable::create(2026, 6, 15, 14, 0, 0, 'UTC')->toIso8601String(),
                )
                ->and($result[0]->endUtc->toIso8601String())->toBe(
                    CarbonImmutable::create(2026, 6, 15, 15, 0, 0, 'UTC')->toIso8601String(),
                );
        });

        it('parses an event with explicit TZID', function (): void {
            $ics = "BEGIN:VCALENDAR\r\n"
                ."BEGIN:VEVENT\r\n"
                ."SUMMARY:Kyiv Session\r\n"
                ."DTSTART;TZID=Europe/Kyiv:20260615T170000\r\n"
                ."DTEND;TZID=Europe/Kyiv:20260615T180000\r\n"
                ."END:VEVENT\r\n"
                ."END:VCALENDAR\r\n";

            $xml = buildReportXml([
                ['href' => '/calendars/alice/work/event-2.ics', 'ics' => $ics],
            ]);

            $result = $this->parser->getParsedReport($xml);

            expect($result)->toHaveCount(1)
                ->and($result[0]->title)->toBe('Kyiv Session')
                ->and($result[0]->providerTimezone)->toBe('Europe/Kyiv')
                ->and($result[0]->startUtc->toIso8601String())->toBe(
                    CarbonImmutable::create(2026, 6, 15, 17, 0, 0, 'Europe/Kyiv')
                        ->setTimezone('UTC')
                        ->toIso8601String(),
                );
        });

        it('parses a floating datetime as app timezone', function (): void {
            $ics = "BEGIN:VCALENDAR\r\n"
                ."BEGIN:VEVENT\r\n"
                ."SUMMARY:Floating Event\r\n"
                ."DTSTART:20260615T100000\r\n"
                ."DTEND:20260615T110000\r\n"
                ."END:VEVENT\r\n"
                ."END:VCALENDAR\r\n";

            $xml = buildReportXml([
                ['href' => '/calendars/alice/work/event-3.ics', 'ics' => $ics],
            ]);

            $result = $this->parser->getParsedReport($xml);

            expect($result)->toHaveCount(1)
                ->and($result[0]->providerTimezone)->toBe(config('app.timezone'))
                ->and($result[0]->startUtc->format('Y-m-d H:i:s'))->toBe('2026-06-15 10:00:00');
        });

        it('unescapes newlines and special chars in description', function (): void {
            $ics = "BEGIN:VCALENDAR\r\n"
                ."BEGIN:VEVENT\r\n"
                ."SUMMARY:Escaped\r\n"
                ."DESCRIPTION:Line 1\\nLine 2\\, with comma\\; and semicolon\\\\ backslash\r\n"
                ."DTSTART:20260615T140000Z\r\n"
                ."DTEND:20260615T150000Z\r\n"
                ."END:VEVENT\r\n"
                ."END:VCALENDAR\r\n";

            $xml = buildReportXml([
                ['href' => '/event.ics', 'ics' => $ics],
            ]);

            $result = $this->parser->getParsedReport($xml);

            expect($result[0]->description)->toBe("Line 1\nLine 2, with comma; and semicolon\\ backslash");
        });

        it('returns empty title when SUMMARY is missing', function (): void {
            $ics = "BEGIN:VCALENDAR\r\n"
                ."BEGIN:VEVENT\r\n"
                ."DTSTART:20260615T140000Z\r\n"
                ."DTEND:20260615T150000Z\r\n"
                ."END:VEVENT\r\n"
                ."END:VCALENDAR\r\n";

            $xml = buildReportXml([
                ['href' => '/event.ics', 'ics' => $ics],
            ]);

            $result = $this->parser->getParsedReport($xml);

            expect($result[0]->title)->toBe('')
                ->and($result[0]->description)->toBeNull();
        });

        it('skips responses with ICS missing DTSTART or DTEND', function (): void {
            $withoutStart = "BEGIN:VCALENDAR\r\n"
                ."BEGIN:VEVENT\r\n"
                ."SUMMARY:No start\r\n"
                ."DTEND:20260615T150000Z\r\n"
                ."END:VEVENT\r\n"
                ."END:VCALENDAR\r\n";

            $withoutEnd = "BEGIN:VCALENDAR\r\n"
                ."BEGIN:VEVENT\r\n"
                ."SUMMARY:No end\r\n"
                ."DTSTART:20260615T140000Z\r\n"
                ."END:VEVENT\r\n"
                ."END:VCALENDAR\r\n";

            $valid = "BEGIN:VCALENDAR\r\n"
                ."BEGIN:VEVENT\r\n"
                ."SUMMARY:Valid\r\n"
                ."DTSTART:20260615T140000Z\r\n"
                ."DTEND:20260615T150000Z\r\n"
                ."END:VEVENT\r\n"
                ."END:VCALENDAR\r\n";

            $xml = buildReportXml([
                ['href' => '/a.ics', 'ics' => $withoutStart],
                ['href' => '/b.ics', 'ics' => $withoutEnd],
                ['href' => '/c.ics', 'ics' => $valid],
            ]);

            $result = $this->parser->getParsedReport($xml);

            expect($result)->toHaveCount(1)
                ->and($result[0]->title)->toBe('Valid');
        });

        it('skips responses where ICS is empty', function (): void {
            $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<D:multistatus xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
  <D:response>
    <D:href>/calendars/empty.ics</D:href>
    <D:propstat>
      <D:prop>
        <C:calendar-data></C:calendar-data>
      </D:prop>
    </D:propstat>
  </D:response>
</D:multistatus>
XML;

            $result = $this->parser->getParsedReport($xml);

            expect($result)->toBe([]);
        });

        it('returns empty array for invalid xml', function (): void {
            $result = $this->parser->getParsedReport('not-xml<<<');

            expect($result)->toBe([]);
        });

        it('keeps absolute hrefs unchanged', function (): void {
            $ics = "BEGIN:VCALENDAR\r\n"
                ."BEGIN:VEVENT\r\n"
                ."SUMMARY:Remote Event\r\n"
                ."DTSTART:20260615T140000Z\r\n"
                ."DTEND:20260615T150000Z\r\n"
                ."END:VEVENT\r\n"
                ."END:VCALENDAR\r\n";

            $xml = buildReportXml([
                ['href' => 'https://other.example.com/calendars/remote.ics', 'ics' => $ics],
            ]);

            $result = $this->parser->getParsedReport($xml);

            expect($result[0]->externalId)->toBe('https://other.example.com/calendars/remote.ics');
        });

        it('parses multiple events and preserves order', function (): void {
            $ics1 = "BEGIN:VCALENDAR\r\n"
                ."BEGIN:VEVENT\r\n"
                ."SUMMARY:First\r\n"
                ."DTSTART:20260615T140000Z\r\n"
                ."DTEND:20260615T150000Z\r\n"
                ."END:VEVENT\r\n"
                ."END:VCALENDAR\r\n";

            $ics2 = "BEGIN:VCALENDAR\r\n"
                ."BEGIN:VEVENT\r\n"
                ."SUMMARY:Second\r\n"
                ."DTSTART:20260616T140000Z\r\n"
                ."DTEND:20260616T150000Z\r\n"
                ."END:VEVENT\r\n"
                ."END:VCALENDAR\r\n";

            $xml = buildReportXml([
                ['href' => '/a.ics', 'ics' => $ics1],
                ['href' => '/b.ics', 'ics' => $ics2],
            ]);

            $result = $this->parser->getParsedReport($xml);

            expect($result)->toHaveCount(2)
                ->and($result[0]->title)->toBe('First')
                ->and($result[1]->title)->toBe('Second');
        });

        it('falls back to DTEND TZID when DTSTART has no TZID', function (): void {
            $ics = "BEGIN:VCALENDAR\r\n"
                ."BEGIN:VEVENT\r\n"
                ."SUMMARY:Mixed\r\n"
                ."DTSTART:20260615T100000\r\n"
                ."DTEND;TZID=Europe/Kyiv:20260615T110000\r\n"
                ."END:VEVENT\r\n"
                ."END:VCALENDAR\r\n";

            $xml = buildReportXml([
                ['href' => '/mixed.ics', 'ics' => $ics],
            ]);

            $result = $this->parser->getParsedReport($xml);

            expect($result[0]->providerTimezone)->toBe('Europe/Kyiv');
        });
    });
});

function buildReportXml(array $events): string
{
    $responses = '';
    foreach ($events as $event) {
        $responses .= sprintf(
            '<D:response><D:href>%s</D:href><D:propstat><D:prop><C:calendar-data><![CDATA[%s]]></C:calendar-data></D:prop></D:propstat></D:response>',
            htmlspecialchars((string) $event['href'], ENT_XML1),
            $event['ics'],
        );
    }

    return '<?xml version="1.0" encoding="UTF-8"?>'
        .'<D:multistatus xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">'
        .$responses
        .'</D:multistatus>';
}
