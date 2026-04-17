<?php

declare(strict_types=1);

namespace App\Services\XmlTools\ExternalCalendar;

use App\DTO\ExternalCalendar\ExternalCalendarEventData;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Throwable;
use XMLReader;

class CalDavReportParser
{
    private const string NS_DAV = 'DAV:';

    private const string NS_CALDAV = 'urn:ietf:params:xml:ns:caldav';

    protected bool $inResponse = false;

    protected bool $inHref = false;

    protected bool $inCalData = false;

    protected ?string $href = null;

    protected ?string $calData = null;

    /** @var list<ExternalCalendarEventData> */
    protected array $events = [];

    public function __construct(private readonly string $caldavRoot) {}

    /**
     * Parses a CalDAV multistatus REPORT response and extracts event data.
     *
     * @return list<ExternalCalendarEventData>
     */
    public function parseReport(string $xml): array
    {
        $reader = new XMLReader;

        if (! $reader->XML($xml, null, LIBXML_NOERROR | LIBXML_NOWARNING)) {
            return [];
        }

        while ($reader->read()) {
            $this->processNode($reader);
        }

        $reader->close();

        return $this->events;
    }

    private function processNode(XMLReader $reader): void
    {
        if ($reader->nodeType === XMLReader::ELEMENT) {
            $this->elementNodeProcessing($reader);
        } elseif ($reader->nodeType === XMLReader::TEXT || $reader->nodeType === XMLReader::CDATA) {
            $this->textNodeProcessing($reader);
        } elseif ($reader->nodeType === XMLReader::END_ELEMENT) {
            $this->endNodeProcessing($reader);
        }
    }

    private function elementNodeProcessing(XMLReader $reader): void
    {
        if ($reader->localName === 'response' && $reader->namespaceURI === self::NS_DAV) {
            $this->inResponse = true;
            $this->href = null;
            $this->calData = null;
        } elseif ($this->inResponse && $reader->localName === 'href' && $reader->namespaceURI === self::NS_DAV) {
            $this->inHref = true;
        } elseif ($this->inResponse && $reader->localName === 'calendar-data' && $reader->namespaceURI === self::NS_CALDAV) {
            $this->inCalData = true;
        }
    }

    private function textNodeProcessing(XMLReader $reader): void
    {
        if ($this->inHref) {
            $this->href = mb_trim($reader->value);
        } elseif ($this->inCalData) {
            $this->calData = ((string) $this->calData).$reader->value;
        }
    }

    private function endNodeProcessing(XMLReader $reader): void
    {
        if ($reader->localName === 'href' && $reader->namespaceURI === self::NS_DAV) {
            $this->inHref = false;
        } elseif ($reader->localName === 'calendar-data' && $reader->namespaceURI === self::NS_CALDAV) {
            $this->inCalData = false;
        } elseif ($reader->localName === 'response' && $reader->namespaceURI === self::NS_DAV) {
            $this->inResponse = false;

            if ($this->href !== null && $this->calData !== null) {
                $event = $this->parseIcsEvent($this->absoluteUrl($this->href), mb_trim($this->calData));

                if ($event instanceof ExternalCalendarEventData) {
                    $this->events[] = $event;
                }
            }
        }
    }

    private function parseIcsEvent(string $eventUrl, string $ics): ?ExternalCalendarEventData
    {
        if ($ics === '') {
            return null;
        }

        $summary = $this->extractIcsValue($ics, 'SUMMARY') ?? '';
        $description = $this->extractIcsValue($ics, 'DESCRIPTION');
        $dtstart = $this->extractIcsValue($ics, 'DTSTART');
        $dtend = $this->extractIcsValue($ics, 'DTEND');
        $tzidStart = $this->extractIcsTzid($ics, 'DTSTART');
        $tzidEnd = $this->extractIcsTzid($ics, 'DTEND');

        if ($dtstart === null || $dtend === null) {
            return null;
        }

        $providerTimezone = $tzidStart ?? $tzidEnd ?? config('app.timezone');

        $startUtc = $this->parseIcsDateTime($dtstart, $tzidStart);
        $endUtc = $this->parseIcsDateTime($dtend, $tzidEnd);

        if (! $startUtc instanceof CarbonInterface || ! $endUtc instanceof CarbonInterface) {
            return null;
        }

        return new ExternalCalendarEventData(
            externalId: $eventUrl,
            title: $summary,
            startUtc: CarbonImmutable::instance($startUtc),
            endUtc: CarbonImmutable::instance($endUtc),
            description: $description !== null ? $this->unescapeIcsText($description) : null,
            providerTimezone: $providerTimezone,
        );
    }

    private function extractIcsValue(string $ics, string $property): ?string
    {
        if (preg_match('/^'.$property.'(?:;[^:]+)?:(.+)$/mi', $ics, $matches)) {
            return mb_trim($matches[1]);
        }

        return null;
    }

    private function extractIcsTzid(string $ics, string $property): ?string
    {
        if (preg_match('/^'.$property.';TZID=([^:]+):/mi', $ics, $matches)) {
            return mb_trim($matches[1]);
        }

        return null;
    }

    private function parseIcsDateTime(string $value, ?string $tzid): ?CarbonImmutable
    {
        try {
            // UTC: value ends with Z (e.g. 20260615T140000Z) — Z is spec-mandated UTC
            if (str_ends_with($value, 'Z')) {
                return CarbonImmutable::createFromFormat('Ymd\THis\Z', $value, 'UTC');
            }

            // With explicit TZID (e.g. 20260615T170000 with TZID=Europe/Kyiv)
            if ($tzid !== null) {
                return CarbonImmutable::createFromFormat('Ymd\THis', $value, $tzid)
                    ->timezone(config('app.timezone'));
            }

            // Floating time — assume app timezone
            return CarbonImmutable::createFromFormat('Ymd\THis', $value, config('app.timezone'));
        } catch (Throwable) {
            return null;
        }
    }

    private function unescapeIcsText(string $text): string
    {
        return str_replace(['\\n', '\\,', '\\;', '\\\\'], ["\n", ',', ';', '\\'], $text);
    }

    private function absoluteUrl(string $path): string
    {
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        return $this->caldavRoot.'/'.mb_ltrim($path, '/');
    }
}
