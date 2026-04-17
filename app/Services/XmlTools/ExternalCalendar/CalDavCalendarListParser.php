<?php

declare(strict_types=1);

namespace App\Services\XmlTools\ExternalCalendar;

use XMLReader;

class CalDavCalendarListParser
{
    private const string NS_DAV = 'DAV:';

    private const string NS_CALDAV = 'urn:ietf:params:xml:ns:caldav';

    protected bool $inResponse = false;

    protected bool $isCalendar = false;

    protected bool $inHref = false;

    protected bool $inDisplayName = false;

    protected ?string $href = null;

    protected ?string $displayName = null;

    /** @var list<array{id: string, name: string, primary: bool}> */
    protected array $calendars = [];

    public function __construct(private readonly string $caldavRoot) {}

    /**
     * Parses a CalDAV PROPFIND multistatus response and returns the list of calendars.
     *
     * @return list<array{id: string, name: string, primary: bool}>
     */
    public function parseCalendarList(string $xml): array
    {
        $reader = new XMLReader;

        if (! $reader->XML($xml, null, LIBXML_NOERROR | LIBXML_NOWARNING)) {
            return [];
        }

        while ($reader->read()) {
            $this->processNode($reader);
        }

        $reader->close();

        return $this->calendars;
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
            $this->isCalendar = false;
            $this->href = null;
            $this->displayName = null;
        } elseif ($this->inResponse) {
            if ($reader->localName === 'href' && $reader->namespaceURI === self::NS_DAV) {
                $this->inHref = true;
            } elseif ($reader->localName === 'displayname' && $reader->namespaceURI === self::NS_DAV) {
                $this->inDisplayName = true;
            } elseif ($reader->localName === 'calendar' && $reader->namespaceURI === self::NS_CALDAV) {
                $this->isCalendar = true;
            }
        }
    }

    private function textNodeProcessing(XMLReader $reader): void
    {
        if ($this->inHref) {
            $this->href = mb_trim($reader->value);
        } elseif ($this->inDisplayName) {
            $this->displayName = ((string) $this->displayName).$reader->value;
        }
    }

    private function endNodeProcessing(XMLReader $reader): void
    {
        if ($reader->localName === 'href' && $reader->namespaceURI === self::NS_DAV) {
            $this->inHref = false;
        } elseif ($reader->localName === 'displayname' && $reader->namespaceURI === self::NS_DAV) {
            $this->inDisplayName = false;
        } elseif ($reader->localName === 'response' && $reader->namespaceURI === self::NS_DAV) {
            $this->inResponse = false;

            if ($this->isCalendar && $this->href !== null) {
                $absoluteHref = $this->absoluteUrl($this->href);
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
    }

    private function absoluteUrl(string $path): string
    {
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        return $this->caldavRoot.'/'.mb_ltrim($path, '/');
    }
}
