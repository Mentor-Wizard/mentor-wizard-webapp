<?php

declare(strict_types=1);

namespace Modules\ExternalCalendar\Services\XmlTools;

use XMLReader;

class CalDavPropfindParser extends AbstractCalDavParser
{
    protected bool $inTarget = false;

    protected bool $inChild = false;

    protected ?string $value = null;

    private bool $found = false;

    private string $currentElementName = '';

    private string $currentChildElement = '';

    /**
     * Extracts the text content of the first matching <childElement> inside
     * any element with the given local name, using XMLReader streaming.
     */
    public function extractValue(string $xml, string $elementName, string $childElement): ?string
    {
        $this->currentElementName = $elementName;
        $this->currentChildElement = $childElement;

        $reader = XMLReader::XML($xml, null, LIBXML_NOERROR | LIBXML_NOWARNING);

        if (! $reader instanceof XMLReader) {
            return null;
        }

        while ($reader->read()) {
            $this->processNode($reader);

            if ($this->found) {
                break;
            }
        }

        $reader->close();

        return $this->value !== null ? mb_trim($this->value) : null;
    }

    protected function elementNodeProcessing(XMLReader $reader): void
    {
        if ($reader->localName === $this->currentElementName) {
            $this->inTarget = true;
        } elseif ($this->inTarget && $reader->localName === $this->currentChildElement) {
            $this->inChild = true;
            $this->value = null;
        }
    }

    protected function textNodeProcessing(XMLReader $reader): void
    {
        if ($this->inChild) {
            $this->value .= $reader->value;
        }
    }

    protected function endNodeProcessing(XMLReader $reader): void
    {
        if ($reader->localName === $this->currentChildElement && $this->inChild) {
            $this->inChild = false;

            if ($this->value !== null) {
                $this->found = true;
            }
        } elseif ($reader->localName === $this->currentElementName) {
            $this->inTarget = false;
        }
    }
}
