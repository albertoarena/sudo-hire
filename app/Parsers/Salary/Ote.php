<?php

namespace App\Parsers\Salary;

/**
 * Reads "OTE" or "on-target earnings" in a salary text. Pure: no I/O.
 * See docs/DECISIONS.md "On-target earnings (OTE)".
 */
final class Ote
{
    public static function in(string $raw): bool
    {
        return preg_match('/\b(OTE|on[\s-]target\s+earnings)\b/iu', $raw) === 1;
    }
}
