<?php

declare(strict_types=1);

namespace App\Services\XmlTools\ExternalCalendar;

use XMLReader;

abstract class AbstractCalDavParser
{
    protected const string NS_DAV = 'DAV:';

    protected const string NS_CALDAV = 'urn:ietf:params:xml:ns:caldav';

    public function __construct(protected readonly string $caldavRoot = '') {}

    abstract protected function elementNodeProcessing(XMLReader $reader): void;

    abstract protected function textNodeProcessing(XMLReader $reader): void;

    abstract protected function endNodeProcessing(XMLReader $reader): void;

    protected function processNode(XMLReader $reader): void
    {
        if ($reader->nodeType === XMLReader::ELEMENT) {
            $this->elementNodeProcessing($reader);
        } elseif ($reader->nodeType === XMLReader::TEXT || $reader->nodeType === XMLReader::CDATA) {
            $this->textNodeProcessing($reader);
        } elseif ($reader->nodeType === XMLReader::END_ELEMENT) {
            $this->endNodeProcessing($reader);
        }
    }

    protected function getAbsoluteUrl(string $path): string
    {
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        return $this->caldavRoot.'/'.mb_ltrim($path, '/');
    }
}
