<?php

namespace App\Sources\Support;

/**
 * HTML job descriptions to plain text. Pure, no I/O.
 * A source with entity-escaped HTML (e.g. Greenhouse) decodes it once first.
 */
final class HtmlToText
{
    public static function convert(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $text = (string) preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $html);
        $text = (string) preg_replace('#<br\s*/?>#i', "\n", $text);
        $text = (string) preg_replace('#<li\b[^>]*>#i', "\n- ", $text);
        $text = (string) preg_replace('#</(p|div|h[1-6]|ul|ol|li|tr|table|blockquote|pre)>|<hr\s*/?>#i', "\n\n", $text);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\u{00A0}", ' ', $text);

        $lines = array_map(
            fn (string $line) => trim((string) preg_replace('/[ \t]+/', ' ', $line)),
            explode("\n", str_replace("\r", '', $text)),
        );

        $text = implode("\n", $lines);
        $text = (string) preg_replace("/\n{3,}/", "\n\n", $text);
        $text = (string) preg_replace("/\n\n(?=- )/", "\n", $text);
        $text = trim($text);

        return $text === '' ? null : $text;
    }
}
