<?php

namespace App\Parsers\Location;

/**
 * Small text helpers for the location parser. Pure: no I/O.
 */
final class Text
{
    /**
     * Removes flag emoji (regional indicators) and other pictographs, e.g. "🇮🇹 Italy" gives " Italy".
     */
    public static function stripEmoji(string $text): string
    {
        return (string) preg_replace('/[\x{1F1E6}-\x{1F1FF}\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}\x{200D}]/u', '', $text);
    }

    /**
     * Trims spaces and the separators left after words are removed, e.g. " - , " or "()".
     * A bracket at the start or the end is trimmed only when it has no pair.
     */
    public static function clean(string $text): string
    {
        $text = (string) preg_replace('/\(\s*\)/u', '', $text);
        $text = (string) preg_replace('/\s+/u', ' ', $text);

        do {
            $before = $text;
            $text = (string) preg_replace('/^[\s,;|\/&\-–—:]+|[\s,;|\/&\-–—:]+$/u', '', $text);

            if (str_starts_with($text, '(') && ! str_contains($text, ')')) {
                $text = substr($text, 1);
            }

            if (str_ends_with($text, ')') && ! str_contains($text, '(')) {
                $text = substr($text, 0, -1);
            }
        } while ($text !== $before);

        return $text;
    }
}
