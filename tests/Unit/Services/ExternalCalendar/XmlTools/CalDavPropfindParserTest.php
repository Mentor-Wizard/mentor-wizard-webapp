<?php

declare(strict_types=1);

use App\Services\XmlTools\ExternalCalendar\CalDavPropfindParser;

mutates(CalDavPropfindParser::class);

describe('CalDavPropfindParser', function (): void {
    beforeEach(function (): void {
        $this->parser = new CalDavPropfindParser;
    });

    describe('extractValue', function (): void {
        it('extracts the text content of the first matching child element', function (): void {
            $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<D:multistatus xmlns:D="DAV:">
  <D:response>
    <D:propstat>
      <D:prop>
        <D:current-user-principal>
          <D:href>/principals/users/alice/</D:href>
        </D:current-user-principal>
      </D:prop>
    </D:propstat>
  </D:response>
</D:multistatus>
XML;

            $result = $this->parser->extractValue($xml, 'current-user-principal', 'href');

            expect($result)->toBe('/principals/users/alice/');
        });

        it('returns the first matching child value when multiple elements exist', function (): void {
            $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<D:multistatus xmlns:D="DAV:">
  <D:response>
    <D:propstat>
      <D:prop>
        <D:calendar-home-set xmlns:C="urn:ietf:params:xml:ns:caldav">
          <D:href>/calendars/alice/</D:href>
          <D:href>/calendars/alice2/</D:href>
        </D:calendar-home-set>
      </D:prop>
    </D:propstat>
  </D:response>
</D:multistatus>
XML;

            $result = $this->parser->extractValue($xml, 'calendar-home-set', 'href');

            expect($result)->toBe('/calendars/alice/');
        });

        it('trims whitespace from extracted values', function (): void {
            $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<D:multistatus xmlns:D="DAV:">
  <D:response>
    <D:propstat>
      <D:prop>
        <D:current-user-principal>
          <D:href>
            /principals/users/alice/
          </D:href>
        </D:current-user-principal>
      </D:prop>
    </D:propstat>
  </D:response>
</D:multistatus>
XML;

            $result = $this->parser->extractValue($xml, 'current-user-principal', 'href');

            expect($result)->toBe('/principals/users/alice/');
        });

        it('returns null when the element is not found', function (): void {
            $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<D:multistatus xmlns:D="DAV:">
  <D:response>
    <D:propstat>
      <D:prop>
        <D:displayname>Work</D:displayname>
      </D:prop>
    </D:propstat>
  </D:response>
</D:multistatus>
XML;

            $result = $this->parser->extractValue($xml, 'current-user-principal', 'href');

            expect($result)->toBeNull();
        });

        it('returns null when the target element exists but child element does not', function (): void {
            $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<D:multistatus xmlns:D="DAV:">
  <D:response>
    <D:propstat>
      <D:prop>
        <D:current-user-principal>
          <D:displayname>Alice</D:displayname>
        </D:current-user-principal>
      </D:prop>
    </D:propstat>
  </D:response>
</D:multistatus>
XML;

            $result = $this->parser->extractValue($xml, 'current-user-principal', 'href');

            expect($result)->toBeNull();
        });

        it('returns null for invalid xml', function (): void {
            $result = $this->parser->extractValue('not-xml<<<', 'current-user-principal', 'href');

            expect($result)->toBeNull();
        });

        it('handles CDATA sections in the child element', function (): void {
            $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<D:multistatus xmlns:D="DAV:">
  <D:response>
    <D:propstat>
      <D:prop>
        <D:current-user-principal>
          <D:href><![CDATA[/principals/users/bob/]]></D:href>
        </D:current-user-principal>
      </D:prop>
    </D:propstat>
  </D:response>
</D:multistatus>
XML;

            $result = $this->parser->extractValue($xml, 'current-user-principal', 'href');

            expect($result)->toBe('/principals/users/bob/');
        });

        it('ignores child element outside of the target element', function (): void {
            $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<D:multistatus xmlns:D="DAV:">
  <D:response>
    <D:href>/outside/</D:href>
    <D:propstat>
      <D:prop>
        <D:current-user-principal>
          <D:href>/principals/users/alice/</D:href>
        </D:current-user-principal>
      </D:prop>
    </D:propstat>
  </D:response>
</D:multistatus>
XML;

            $result = $this->parser->extractValue($xml, 'current-user-principal', 'href');

            expect($result)->toBe('/principals/users/alice/');
        });

        it('does not return value from a second target element after the first closes', function (): void {
            $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<D:multistatus xmlns:D="DAV:">
  <D:response>
    <D:propstat>
      <D:prop>
        <D:current-user-principal>
          <D:href>/principals/users/alice/</D:href>
        </D:current-user-principal>
        <D:current-user-principal>
          <D:href>/principals/users/bob/</D:href>
        </D:current-user-principal>
      </D:prop>
    </D:propstat>
  </D:response>
</D:multistatus>
XML;

            $result = $this->parser->extractValue($xml, 'current-user-principal', 'href');

            expect($result)->toBe('/principals/users/alice/');
        });

        it('works regardless of namespace prefix', function (): void {
            $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<multistatus xmlns="DAV:">
  <response>
    <propstat>
      <prop>
        <current-user-principal>
          <href>/principals/users/charlie/</href>
        </current-user-principal>
      </prop>
    </propstat>
  </response>
</multistatus>
XML;

            $result = $this->parser->extractValue($xml, 'current-user-principal', 'href');

            expect($result)->toBe('/principals/users/charlie/');
        });
    });
});
