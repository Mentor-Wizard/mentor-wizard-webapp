<?php

declare(strict_types=1);

use Modules\ExternalCalendar\Traits\XmlAppleCalendarRequests;

describe('XmlAppleCalendarRequests trait', function (): void {
    beforeEach(function (): void {
        $this->instance = new class
        {
            use XmlAppleCalendarRequests;

            public function callCalendarQueryReport(string $from, string $to): string
            {
                return $this->calendarQueryReport($from, $to);
            }

            public function callPropfindCurrentUserPrincipal(): string
            {
                return $this->propfindCurrentUserPrincipal();
            }

            public function callPropfindCalendarHome(): string
            {
                return $this->propfindCalendarHome();
            }

            public function callPropfindCalendarList(): string
            {
                return $this->propfindCalendarList();
            }
        };
    });

    describe('calendarQueryReport', function (): void {
        it('returns an XML string with calendar-query and time-range elements', function (): void {
            $xml = $this->instance->callCalendarQueryReport('20260101T000000Z', '20260201T000000Z');

            expect($xml)->toBeString()
                ->and($xml)->not->toBeEmpty()
                ->and($xml)->toStartWith('<?xml version="1.0" encoding="UTF-8"?>')
                ->and($xml)->toContain('<C:calendar-query')
                ->and($xml)->toContain('xmlns:C="urn:ietf:params:xml:ns:caldav"')
                ->and($xml)->toContain('xmlns:D="DAV:"')
                ->and($xml)->toContain('<D:getetag/>')
                ->and($xml)->toContain('<C:calendar-data/>')
                ->and($xml)->toContain('<C:comp-filter name="VCALENDAR">')
                ->and($xml)->toContain('<C:comp-filter name="VEVENT">')
                ->and($xml)->toContain('<C:time-range start="20260101T000000Z" end="20260201T000000Z"/>')
                ->and($xml)->toContain('</C:calendar-query>');
        });

        it('interpolates from and to values directly into the XML', function (): void {
            $xml = $this->instance->callCalendarQueryReport('FROM-VALUE', 'TO-VALUE');

            expect($xml)->toContain('start="FROM-VALUE"')
                ->and($xml)->toContain('end="TO-VALUE"');
        });

        it('produces well-formed XML', function (): void {
            $xml = $this->instance->callCalendarQueryReport('20260101T000000Z', '20260201T000000Z');

            $doc = new DOMDocument;
            $loaded = $doc->loadXML($xml);

            expect($loaded)->toBeTrue();
        });

        it('escapes XML special characters in from and to to prevent injection', function (): void {
            $malicious = '"/><injected>&';

            $xml = $this->instance->callCalendarQueryReport($malicious, $malicious);

            expect($xml)->toContain('&lt;injected&gt;')
                ->and($xml)->toContain('&amp;')
                ->and($xml)->toContain('&quot;')
                ->and($xml)->not->toContain('<injected>');
        });

        it('produces well-formed XML even with malicious from and to values', function (): void {
            $malicious = '"/><injected>&';

            $xml = $this->instance->callCalendarQueryReport($malicious, $malicious);

            $doc = new DOMDocument;
            $loaded = $doc->loadXML($xml);

            expect($loaded)->toBeTrue();
        });
    });

    describe('propfindCurrentUserPrincipal', function (): void {
        it('returns a PROPFIND XML requesting current-user-principal', function (): void {
            $xml = $this->instance->callPropfindCurrentUserPrincipal();

            expect($xml)->toBeString()
                ->and($xml)->not->toBeEmpty()
                ->and($xml)->toStartWith('<?xml version="1.0" encoding="UTF-8"?>')
                ->and($xml)->toContain('<D:propfind')
                ->and($xml)->toContain('xmlns:D="DAV:"')
                ->and($xml)->toContain('<D:current-user-principal/>')
                ->and($xml)->toContain('</D:propfind>');
        });

        it('produces well-formed XML', function (): void {
            $xml = $this->instance->callPropfindCurrentUserPrincipal();

            $doc = new DOMDocument;
            $loaded = $doc->loadXML($xml);

            expect($loaded)->toBeTrue();
        });
    });

    describe('propfindCalendarHome', function (): void {
        it('returns a PROPFIND XML requesting calendar-home-set', function (): void {
            $xml = $this->instance->callPropfindCalendarHome();

            expect($xml)->toBeString()
                ->and($xml)->not->toBeEmpty()
                ->and($xml)->toStartWith('<?xml version="1.0" encoding="UTF-8"?>')
                ->and($xml)->toContain('<D:propfind')
                ->and($xml)->toContain('xmlns:D="DAV:"')
                ->and($xml)->toContain('xmlns:C="urn:ietf:params:xml:ns:caldav"')
                ->and($xml)->toContain('<C:calendar-home-set/>')
                ->and($xml)->toContain('</D:propfind>');
        });

        it('produces well-formed XML', function (): void {
            $xml = $this->instance->callPropfindCalendarHome();

            $doc = new DOMDocument;
            $loaded = $doc->loadXML($xml);

            expect($loaded)->toBeTrue();
        });
    });

    describe('propfindCalendarList', function (): void {
        it('returns a PROPFIND XML requesting resourcetype, displayname, and getctag', function (): void {
            $xml = $this->instance->callPropfindCalendarList();

            expect($xml)->toBeString()
                ->and($xml)->not->toBeEmpty()
                ->and($xml)->toStartWith('<?xml version="1.0" encoding="UTF-8"?>')
                ->and($xml)->toContain('<D:propfind')
                ->and($xml)->toContain('xmlns:D="DAV:"')
                ->and($xml)->toContain('xmlns:C="urn:ietf:params:xml:ns:caldav"')
                ->and($xml)->toContain('xmlns:CS="http://calendarserver.org/ns/"')
                ->and($xml)->toContain('<D:resourcetype/>')
                ->and($xml)->toContain('<D:displayname/>')
                ->and($xml)->toContain('<CS:getctag/>')
                ->and($xml)->toContain('</D:propfind>');
        });

        it('produces well-formed XML', function (): void {
            $xml = $this->instance->callPropfindCalendarList();

            $doc = new DOMDocument;
            $loaded = $doc->loadXML($xml);

            expect($loaded)->toBeTrue();
        });
    });
});
