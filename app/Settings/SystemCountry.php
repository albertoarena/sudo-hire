<?php

namespace App\Settings;

use App\Parsers\Location\Countries;
use Illuminate\Support\Facades\Process;

/**
 * The country of the Mac region, from the AppleLocale preference, e.g. "en_IT@currency=eur"
 * gives IT. A region override ("rg=itzzzz") wins. Null when the region is not in the country
 * table. The first run uses it as the preset. Not final: tests replace it with a fake.
 */
class SystemCountry
{
    public function code(): ?string
    {
        $result = Process::timeout(5)->run('defaults read -g AppleLocale');

        return $result->successful() ? self::fromLocale($result->output()) : null;
    }

    public static function fromLocale(string $locale): ?string
    {
        $locale = trim($locale);

        if (preg_match('/[@;]rg=([a-z]{2})zzzz/i', $locale, $match) === 1) {
            return self::known($match[1]);
        }

        if (preg_match('/^[a-z]{2,3}(?:-[A-Za-z]{4})?_([A-Z]{2})(?:@|$)/', $locale, $match) === 1) {
            return self::known($match[1]);
        }

        return null;
    }

    private static function known(string $code): ?string
    {
        $code = strtoupper($code);

        return in_array($code, Countries::codes(), true) ? $code : null;
    }
}
