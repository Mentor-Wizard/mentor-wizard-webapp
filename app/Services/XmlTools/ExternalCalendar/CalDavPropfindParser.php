<?php

declare(strict_types=1);

namespace App\Services\XmlTools\ExternalCalendar;

use XMLReader;

class CalDavPropfindParser
{
    protected bool $inTarget = false;

    protected bool $inChild = false;

    protected ?string $value = null;

    /**
     * Extracts the text content of the first matching <childElement> inside
     * any element with the given local name, using XMLReader streaming.
     */
    public function extractValue(string $xml, string $elementName, string $childElement): ?string
    {
        $reader = new XMLReader;

        if (! $reader->XML($xml, null, LIBXML_NOERROR | LIBXML_NOWARNING)) {
            return null;
        }

        while ($reader->read()) {
            if ($this->processNode($reader, $elementName, $childElement)) {
                break;
            }
        }

        $reader->close();

        return $this->value !== null ? mb_trim($this->value) : null;
    }

    private function processNode(XMLReader $reader, string $elementName, string $childElement): bool
    {
        if ($reader->nodeType === XMLReader::ELEMENT) {
            $this->elementNodeProcessing($reader, $elementName, $childElement);
        } elseif ($reader->nodeType === XMLReader::TEXT || $reader->nodeType === XMLReader::CDATA) {
            $this->TextCDATAnodeProcessings($reader);
        } elseif ($reader->nodeType === XMLReader::END_ELEMENT) {
            return $this->endNodeProcessing($reader, $elementName, $childElement);
        }

        return false;
    }

    private function elementNodeProcessing(XMLReader $reader, string $elementName, string $childElement): void
    {
        if ($reader->localName === $elementName) {
            $this->inTarget = true;
        } elseif ($this->inTarget && $reader->localName === $childElement) {
            $this->inChild = true;
            $this->value = null;
        }
    }

    private function TextCDATAnodeProcessings(XMLReader $reader): void
    {
        if ($this->inChild) {
            $this->value = ((string) $this->value).$reader->value;
        }
    }

    private function endNodeProcessing(XMLReader $reader, string $elementName, string $childElement): bool
    {
        if ($reader->localName === $childElement && $this->inChild) {
            $this->inChild = false;

            if ($this->value !== null) {
                return true;
            }
        } elseif ($reader->localName === $elementName) {
            $this->inTarget = false;
        }

        return false;
    }
}
