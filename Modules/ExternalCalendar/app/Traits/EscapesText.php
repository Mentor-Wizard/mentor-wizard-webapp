<?php

declare(strict_types=1);

namespace Modules\ExternalCalendar\Traits;

trait EscapesText
{
    /**
     * Escapes text for iCalendar (RFC 5545).
     */
    protected function escapeText(string $text): string
    {
        return str_replace(
            ['\\', ';', ',', "\r\n", "\n", "\r"],
            ['\\\\', '\;', '\,', '\\n', '\\n', '\\n'],
            $text
        );
    }
}
