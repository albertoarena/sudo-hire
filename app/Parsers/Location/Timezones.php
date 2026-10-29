<?php

namespace App\Parsers\Location;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Reads timezone text ("CET", "UTC+1 ±2", "USA timezones", "Europe/Berlin")
 * into UTC offsets in decimal hours. Timezones are never regions.
 * Pure: uses the timezone data built into PHP, no I/O.
 */
final class Timezones
{
    /** Offsets of a zone are read over this year, so summer time is included. */
    private const YEAR = 2026;

    private const MIN = -12.0;

    private const MAX = 14.0;

    /** @var array<string, string> abbreviation to a zone that uses it */
    private const ABBREVIATIONS = [
        'UTC' => 'UTC', 'GMT' => 'UTC', 'BST' => 'Europe/London', 'WET' => 'Europe/Lisbon',
        'CET' => 'Europe/Berlin', 'CEST' => 'Europe/Berlin', 'EET' => 'Europe/Athens', 'EEST' => 'Europe/Athens',
        'MSK' => 'Europe/Moscow', 'EST' => 'America/New_York', 'EDT' => 'America/New_York',
        'CST' => 'America/Chicago', 'CDT' => 'America/Chicago', 'MST' => 'America/Denver', 'MDT' => 'America/Denver',
        'PST' => 'America/Los_Angeles', 'PDT' => 'America/Los_Angeles', 'AKST' => 'America/Anchorage',
        'HST' => 'Pacific/Honolulu', 'JST' => 'Asia/Tokyo', 'SGT' => 'Asia/Singapore',
        'AEST' => 'Australia/Sydney', 'AEDT' => 'Australia/Sydney', 'NZST' => 'Pacific/Auckland', 'NZDT' => 'Pacific/Auckland',
    ];

    /** @var array<string, string> zone name in text to a zone that uses it */
    private const NAMES = [
        'central european' => 'Europe/Berlin', 'eastern european' => 'Europe/Athens', 'western european' => 'Europe/Lisbon',
        'eastern' => 'America/New_York', 'central' => 'America/Chicago', 'mountain' => 'America/Denver', 'pacific' => 'America/Los_Angeles',
    ];

    private const SPREAD = '(?:\s*(?:±|\+\/-|\+-)\s*(\d{1,2}(?:\.\d+)?))?';

    private const SUFFIX = '(?:\s+time[\s-]?zones?|\s+time)?';

    /** @var list<float> */
    private array $offsets = [];

    /**
     * @return array{0: string, 1: float|null, 2: float|null} the text without the timezone parts, the min and the max offset
     */
    public static function extract(string $text): array
    {
        $reader = new self;

        $text = $reader->offsets($text);
        $text = $reader->zoneIds($text);
        $text = $reader->abbreviations($text);
        $text = $reader->names($text);
        $text = $reader->countries($text);

        if ($reader->offsets === []) {
            return [$text, null, null];
        }

        return [$text, max(self::MIN, min($reader->offsets)), min(self::MAX, max($reader->offsets))];
    }

    /** "UTC+1", "GMT-3:30", "UTC+1 ±2" */
    private function offsets(string $text): string
    {
        return (string) preg_replace_callback(
            '/\b(?:UTC|GMT)\s*([+\-−])\s*(\d{1,2})(?::(\d{2}))?'.self::SPREAD.'/iu',
            function (array $match) {
                $sign = $match[1] === '+' ? 1 : -1;
                $offset = $sign * ((int) $match[2] + (int) ($match[3] ?? 0) / 60);
                $this->add($offset, $offset, (float) ($match[4] ?? 0));

                return ' ';
            },
            $text,
        );
    }

    /** "Europe/Berlin", "Europe/Berlin Time Zone" */
    private function zoneIds(string $text): string
    {
        return (string) preg_replace_callback(
            '/\b([A-Z][A-Za-z]+(?:\/[A-Z][A-Za-z_\-]+)+)'.self::SUFFIX.'\b/u',
            function (array $match) {
                if (! in_array($match[1], DateTimeZone::listIdentifiers(), true)) {
                    return $match[0];
                }

                $this->addZones([$match[1]], 0);

                return ' ';
            },
            $text,
        );
    }

    /** "CET", "EST ±2" */
    private function abbreviations(string $text): string
    {
        $pattern = implode('|', array_keys(self::ABBREVIATIONS));

        return (string) preg_replace_callback(
            '/\b('.$pattern.')\b'.self::SPREAD.self::SUFFIX.'/u',
            function (array $match) {
                $this->addZones([self::ABBREVIATIONS[$match[1]]], (float) ($match[2] ?? 0));

                return ' ';
            },
            $text,
        );
    }

    /** "Eastern Time", "Central European Time" */
    private function names(string $text): string
    {
        $pattern = implode('|', array_keys(self::NAMES));

        return (string) preg_replace_callback(
            '/\b('.$pattern.')\s+time(?:[\s-]?zones?)?\b/iu',
            function (array $match) {
                $this->addZones([self::NAMES[mb_strtolower($match[1])]], 0);

                return ' ';
            },
            $text,
        );
    }

    /**
     * "USA timezones" gives the zones of the country. Other "<word> time zones"
     * text is removed without offsets: "European time zones" is not a region.
     */
    private function countries(string $text): string
    {
        return (string) preg_replace_callback(
            '/((?:[\p{L}.\']+\s+){0,4})time[\s-]?zones?\b/iu',
            function (array $match) {
                $words = preg_split('/\s+/u', trim($match[1]), -1, PREG_SPLIT_NO_EMPTY) ?: [];

                for ($start = 0; $start < count($words); $start++) {
                    $code = Countries::code(implode(' ', array_slice($words, $start)));

                    if ($code !== null) {
                        $this->addZones(DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, $code), 0);

                        return implode(' ', array_slice($words, 0, $start)).' ';
                    }
                }

                return implode(' ', array_slice($words, 0, -1)).' ';
            },
            $text,
        );
    }

    /**
     * @param  list<string>  $zones
     */
    private function addZones(array $zones, float $spread): void
    {
        $start = new DateTimeImmutable(self::YEAR.'-01-01T00:00:00Z');
        $end = new DateTimeImmutable(self::YEAR.'-12-31T23:59:59Z');

        foreach ($zones as $zone) {
            $transitions = (new DateTimeZone($zone))->getTransitions($start->getTimestamp(), $end->getTimestamp()) ?: [];
            $hours = array_map(fn (array $transition) => $transition['offset'] / 3600, $transitions);

            if ($hours !== []) {
                $this->add(min($hours), max($hours), $spread);
            }
        }
    }

    private function add(float $min, float $max, float $spread): void
    {
        $this->offsets[] = (float) ($min - $spread);
        $this->offsets[] = (float) ($max + $spread);
    }
}
