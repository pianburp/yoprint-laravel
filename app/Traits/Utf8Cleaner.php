<?php

namespace App\Traits;

class Utf8Cleaner
{
    /**
     * Clean non-UTF-8 characters from string
     *
     * @param mixed $string
     * @return mixed
     */
    public static function cleanUtf8($string)
    {
        if (!is_string($string)) {
            return $string;
        }

        // Convert to UTF-8 and remove invalid sequences
        $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');

        // Remove zero-width and problematic Unicode characters
        return preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $string);
    }
}
