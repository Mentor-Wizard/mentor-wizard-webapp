<?php

declare(strict_types=1);

namespace App\Services\XmlTools\ExternalCalendar;

use XMLReader;

class CalDavCalendarListParser extends AbstractCalDavParser
{
    protected bool $inResponse = false;

    protected bool $isCalendar = false;

    protected bool $inHref = false;

    protected bool $inDisplayName = false;

    protected ?string $href = null;

    protected ?string $displayName = null;

    /** @var list<array{id: string, name: string, primary: bool}> */
    protected array $calendars = [];

    /**
     * Parses a CalDAV PROPFIND multistatus response and returns the list of calendars.
     *
     * @return list<array{id: string, name: string, primary: bool}>
     */
    public function getParsedCalendarList(string $xml): array
    {
        $reader = XMLReader::XML($xml, null, LIBXML_NOERROR | LIBXML_NOWARNING);

        if (! $reader instanceof XMLReader) {
            return [];
        }

        while ($reader->read()) {
            $this->processNode($reader);
        }

        $reader->close();

        return $this->calendars;
    }

    protected function elementNodeProcessing(XMLReader $reader): void
    {
        if ($reader->localName === 'response' && $reader->namespaceURI === self::NS_DAV) {
            $this->inResponse = true;
            $this->isCalendar = false;
            $this->href = null;
            $this->displayName = null;

            return;
        }

        if ($this->inResponse) {
            $this->processResponseElement($reader);
        }
    }

    protected function textNodeProcessing(XMLReader $reader): void
    {
        if ($this->inHref) {
            $this->href = mb_trim($reader->value);
        } elseif ($this->inDisplayName) {
            $this->displayName .= $reader->value;
        }
    }

    protected function endNodeProcessing(XMLReader $reader): void
    {
        if ($reader->localName === 'href' && $reader->namespaceURI === self::NS_DAV) {
            $this->inHref = false;

            return;
        }

        if ($reader->localName === 'displayname' && $reader->namespaceURI === self::NS_DAV) {
            $this->inDisplayName = false;

            return;
        }

        if ($reader->localName !== 'response' || $reader->namespaceURI !== self::NS_DAV) {
            return;
        }

        $this->inResponse = false;
        $this->appendCalendarIfValid();
    }

    private function processResponseElement(XMLReader $reader): void
    {
        if ($reader->localName === 'href' && $reader->namespaceURI === self::NS_DAV) {
            $this->inHref = true;
        } elseif ($reader->localName === 'displayname' && $reader->namespaceURI === self::NS_DAV) {
            $this->inDisplayName = true;
        } elseif ($reader->localName === 'calendar' && $reader->namespaceURI === self::NS_CALDAV) {
            $this->isCalendar = true;
        }
    }

    private function appendCalendarIfValid(): void
    {
        if (! $this->isCalendar || $this->href === null) {
            return;
        }

        $absoluteHref = $this->getAbsoluteUrl($this->href);
        $name = ($this->displayName !== null && $this->displayName !== '')
            ? $this->displayName
            : basename(mb_rtrim($absoluteHref, '/'));

        $this->calendars[] = [
            'id'      => $absoluteHref,
            'name'    => $name,
            'primary' => str_contains($absoluteHref, 'home') || str_contains(mb_strtolower($name), 'home'),
        ];
    }
}
