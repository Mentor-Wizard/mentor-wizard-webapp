<?php

declare(strict_types=1);

use Modules\ExternalCalendar\Services\XmlTools\CalDavCalendarListParser;

mutates(CalDavCalendarListParser::class);

describe('CalDavCalendarListParser', function (): void {
    beforeEach(function (): void {
        $this->caldavRoot = 'https://caldav.example.com';
        $this->parser = new CalDavCalendarListParser($this->caldavRoot);
    });

    describe('getParsedCalendarList', function (): void {
        it('parses a single calendar response with display name', function (): void {
            $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<D:multistatus xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
  <D:response>
    <D:href>/calendars/alice/work/</D:href>
    <D:propstat>
      <D:prop>
        <D:resourcetype>
          <D:collection/>
          <C:calendar/>
        </D:resourcetype>
        <D:displayname>Work</D:displayname>
      </D:prop>
    </D:propstat>
  </D:response>
</D:multistatus>
XML;

            $result = $this->parser->getParsedCalendarList($xml);

            expect($result)->toHaveCount(1)
                ->and($result[0]['id'])->toBe('https://caldav.example.com/calendars/alice/work/')
                ->and($result[0]['name'])->toBe('Work')
                ->and($result[0]['primary'])->toBeFalse();
        });

        it('skips responses that are not calendars', function (): void {
            $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<D:multistatus xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
  <D:response>
    <D:href>/calendars/alice/</D:href>
    <D:propstat>
      <D:prop>
        <D:resourcetype>
          <D:collection/>
        </D:resourcetype>
        <D:displayname>Not a calendar</D:displayname>
      </D:prop>
    </D:propstat>
  </D:response>
  <D:response>
    <D:href>/calendars/alice/work/</D:href>
    <D:propstat>
      <D:prop>
        <D:resourcetype>
          <D:collection/>
          <C:calendar/>
        </D:resourcetype>
        <D:displayname>Work</D:displayname>
      </D:prop>
    </D:propstat>
  </D:response>
</D:multistatus>
XML;

            $result = $this->parser->getParsedCalendarList($xml);

            expect($result)->toHaveCount(1)
                ->and($result[0]['name'])->toBe('Work');
        });

        it('prepends caldavRoot for relative hrefs', function (): void {
            $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<D:multistatus xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
  <D:response>
    <D:href>/calendars/alice/work/</D:href>
    <D:propstat>
      <D:prop>
        <D:resourcetype>
          <D:collection/>
          <C:calendar/>
        </D:resourcetype>
        <D:displayname>Work</D:displayname>
      </D:prop>
    </D:propstat>
  </D:response>
</D:multistatus>
XML;

            $result = $this->parser->getParsedCalendarList($xml);

            expect($result[0]['id'])->toBe('https://caldav.example.com/calendars/alice/work/');
        });

        it('keeps absolute hrefs intact', function (): void {
            $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<D:multistatus xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
  <D:response>
    <D:href>https://other.example.com/calendars/alice/work/</D:href>
    <D:propstat>
      <D:prop>
        <D:resourcetype>
          <D:collection/>
          <C:calendar/>
        </D:resourcetype>
        <D:displayname>Remote</D:displayname>
      </D:prop>
    </D:propstat>
  </D:response>
</D:multistatus>
XML;

            $result = $this->parser->getParsedCalendarList($xml);

            expect($result[0]['id'])->toBe('https://other.example.com/calendars/alice/work/');
        });

        it('marks primary when href contains "home"', function (): void {
            $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<D:multistatus xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
  <D:response>
    <D:href>/calendars/alice/home/</D:href>
    <D:propstat>
      <D:prop>
        <D:resourcetype>
          <D:collection/>
          <C:calendar/>
        </D:resourcetype>
        <D:displayname>My Calendar</D:displayname>
      </D:prop>
    </D:propstat>
  </D:response>
</D:multistatus>
XML;

            $result = $this->parser->getParsedCalendarList($xml);

            expect($result[0]['primary'])->toBeTrue();
        });

        it('marks primary when display name contains "home" case-insensitively', function (): void {
            $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<D:multistatus xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
  <D:response>
    <D:href>/calendars/alice/primary/</D:href>
    <D:propstat>
      <D:prop>
        <D:resourcetype>
          <D:collection/>
          <C:calendar/>
        </D:resourcetype>
        <D:displayname>HOME</D:displayname>
      </D:prop>
    </D:propstat>
  </D:response>
</D:multistatus>
XML;

            $result = $this->parser->getParsedCalendarList($xml);

            expect($result[0]['primary'])->toBeTrue();
        });

        it('falls back to basename when display name is empty', function (): void {
            $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<D:multistatus xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
  <D:response>
    <D:href>/calendars/alice/work/</D:href>
    <D:propstat>
      <D:prop>
        <D:resourcetype>
          <D:collection/>
          <C:calendar/>
        </D:resourcetype>
        <D:displayname></D:displayname>
      </D:prop>
    </D:propstat>
  </D:response>
</D:multistatus>
XML;

            $result = $this->parser->getParsedCalendarList($xml);

            expect($result[0]['name'])->toBe('work');
        });

        it('falls back to basename when display name is missing', function (): void {
            $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<D:multistatus xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
  <D:response>
    <D:href>/calendars/alice/personal/</D:href>
    <D:propstat>
      <D:prop>
        <D:resourcetype>
          <D:collection/>
          <C:calendar/>
        </D:resourcetype>
      </D:prop>
    </D:propstat>
  </D:response>
</D:multistatus>
XML;

            $result = $this->parser->getParsedCalendarList($xml);

            expect($result[0]['name'])->toBe('personal');
        });

        it('returns an empty array for invalid xml', function (): void {
            $result = $this->parser->getParsedCalendarList('not-xml<<<');

            expect($result)->toBeEmpty();
        });

        it('returns multiple calendars in order', function (): void {
            $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<D:multistatus xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
  <D:response>
    <D:href>/calendars/alice/home/</D:href>
    <D:propstat>
      <D:prop>
        <D:resourcetype>
          <D:collection/>
          <C:calendar/>
        </D:resourcetype>
        <D:displayname>Personal</D:displayname>
      </D:prop>
    </D:propstat>
  </D:response>
  <D:response>
    <D:href>/calendars/alice/work/</D:href>
    <D:propstat>
      <D:prop>
        <D:resourcetype>
          <D:collection/>
          <C:calendar/>
        </D:resourcetype>
        <D:displayname>Work</D:displayname>
      </D:prop>
    </D:propstat>
  </D:response>
</D:multistatus>
XML;

            $result = $this->parser->getParsedCalendarList($xml);

            expect($result)->toHaveCount(2)
                ->and($result[0]['name'])->toBe('Personal')
                ->and($result[0]['primary'])->toBeTrue()
                ->and($result[1]['name'])->toBe('Work')
                ->and($result[1]['primary'])->toBeFalse();
        });

        it('does not mark primary when neither href nor name contains "home"', function (): void {
            $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<D:multistatus xmlns:D="DAV:" xmlns:C="urn:ietf:params:xml:ns:caldav">
  <D:response>
    <D:href>/calendars/alice/work/</D:href>
    <D:propstat>
      <D:prop>
        <D:resourcetype>
          <D:collection/>
          <C:calendar/>
        </D:resourcetype>
        <D:displayname>Work</D:displayname>
      </D:prop>
    </D:propstat>
  </D:response>
</D:multistatus>
XML;

            $result = $this->parser->getParsedCalendarList($xml);

            expect($result[0]['primary'])->toBeFalse();
        });
    });
});
