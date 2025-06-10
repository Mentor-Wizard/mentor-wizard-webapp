<?php

declare(strict_types=1);

namespace App\Support;

class StringHelper
{
    /**
     * Truncate a string to a specified length and append an ellipsis if truncated.
     *
     * @param  string  $string  The string to truncate
     * @param  int  $length  The maximum length of the string
     * @param  string  $ellipsis  The string to append if truncated
     * @return string The truncated string
     */
    public static function truncate(string $string, int $length = 100, string $ellipsis = '...'): string
    {
        if (mb_strlen($string) <= $length) {
            return $string;
        }

        return mb_substr($string, 0, $length).$ellipsis;
    }
}
