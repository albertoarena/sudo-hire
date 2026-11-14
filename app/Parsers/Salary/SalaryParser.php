<?php

namespace App\Parsers\Salary;

use App\Schema\Salary;
use App\Schema\SalaryPeriod;

/**
 * Reads one free text salary into a Salary (REQUIREMENTS section 7). Pure: no I/O.
 *
 * The first amount with a currency wins: one amount or a range ("60-70k EUR").
 * The period comes from the same sentence. Text the rules cannot read gives null.
 */
final class SalaryParser
{
    /**
     * ISO 4217 codes the parser reads in text: the ECB reference currencies and EUR.
     * A code counts only when it touches an amount ("1000PHP", "PHP 50,000"),
     * so "PHP" in "Senior PHP developer" stays the language.
     */
    private const array CODES = [
        'EUR', 'USD', 'JPY', 'CZK', 'DKK', 'GBP', 'HUF', 'PLN', 'RON', 'SEK', 'CHF', 'ISK', 'NOK', 'TRY',
        'AUD', 'BRL', 'CAD', 'CNY', 'HKD', 'IDR', 'ILS', 'INR', 'KRW', 'MXN', 'MYR', 'NZD', 'PHP', 'SGD', 'THB', 'ZAR',
    ];

    /** "PHP" before a number below this is a language version ("PHP 8.3"), not pesos. */
    private const float PHP_VERSION_LIMIT = 100;

    /** Symbols with one meaning. */
    private const array SYMBOLS = [
        'A$' => 'AUD', 'AU$' => 'AUD', 'NZ$' => 'NZD', 'C$' => 'CAD', 'CA$' => 'CAD', 'US$' => 'USD',
        'HK$' => 'HKD', 'S$' => 'SGD', 'SG$' => 'SGD', 'R$' => 'BRL', '€' => 'EUR', '£' => 'GBP', '₹' => 'INR', '₱' => 'PHP',
    ];

    /** Symbols that need the currency hint. A bare "$" without a hint is USD. "¥" is JPY or CNY. */
    private const array GENERIC = ['$' => 'USD', '¥' => null];

    /** The currencies a bare symbol can stand for, next to a code ("$162,000 USD"). */
    private const array GENERIC_FITS = [
        '$' => ['USD', 'AUD', 'NZD', 'CAD', 'HKD', 'SGD', 'BRL', 'MXN'],
        '¥' => ['JPY', 'CNY'],
    ];

    private const array PERIODS = [
        'hour' => '/\b(per|an|a)\s+hour\b|\/\s*(hour|hr)\b|\bhourly\b|\bp\/h\b/i',
        'day' => '/\b(per|a)\s+day\b|\/\s*day\b|\bdaily\b|\bday\s+rate\b|\bp\/d\b/i',
        'month' => '/\b(per|a)\s+month\b|\/\s*(month|mo)\b|\bmonthly\b|\bp\/m\b/i',
        'year' => '/\b(per|a)\s+year\b|\/\s*(year|yr)\b|\b(annual|annually|yearly|per\s+annum)\b|\bp\.a\./i',
    ];

    /**
     * @param  string|null  $currencyHint  ISO 4217 code the source gives, used for a bare "$" or "¥"
     */
    public function parse(string $text, ?string $currencyHint = null): ?Salary
    {
        if ($currencyHint !== null && preg_match('/^[A-Z]{3}$/', $currencyHint) !== 1) {
            return null;
        }

        foreach ($this->sentences($text) as $sentence) {
            $amount = $this->firstAmount($sentence, $currencyHint);

            if ($amount !== null) {
                [$min, $max, $currency] = $amount;

                return $currency === null ? null : new Salary($min, $max, $currency, $this->period($sentence), $text);
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function sentences(string $text): array
    {
        return array_values(array_filter(
            (array) preg_split('/(?<=[.!?])\s+|\n+/u', $text),
            fn ($sentence) => is_string($sentence) && trim($sentence) !== '',
        ));
    }

    /**
     * The first amount or range with a currency marker. Null when there is none.
     * The currency is null when the marker cannot be read (e.g. "¥" with no hint).
     *
     * @return array{0: float|null, 1: float|null, 2: string|null}|null
     */
    private function firstAmount(string $sentence, ?string $hint): ?array
    {
        $tokens = $this->tokens($sentence);

        for ($i = 0; $i < count($tokens); $i++) {
            $first = $tokens[$i];
            $second = $tokens[$i + 1] ?? null;
            $isRange = $second !== null
                && preg_match('/^\s*(-|–|—|to)\s*$/u', substr($sentence, $first['end'], $second['start'] - $first['end'])) === 1;

            $parts = $isRange ? [$first, $second] : [$first];
            $markers = array_merge(...array_map(fn (array $token) => $token['markers'], $parts));

            $before = substr($sentence, 0, $first['start']);
            $after = substr($sentence, $parts[array_key_last($parts)]['end']);

            if ($markers === [] || $this->isBenefit($before, $after)) {
                $i += $isRange ? 1 : 0;

                continue;
            }

            if (! $isRange) {
                $currency = $this->currency($markers, $hint);

                return preg_match('/\bup\s+to\s*$/iu', $before) === 1
                    ? [null, $first['value'], $currency]
                    : [$first['value'], null, $currency];
            }

            [$min, $max] = $this->applyThousands($first, $second);

            // A range with min above max cannot be read: no currency, so no salary.
            return [$min, $max, $min > $max ? null : $this->currency($markers, $hint)];
        }

        return null;
    }

    /**
     * An amount that belongs to an allowance or a benefit is not pay: "benefit of 7500PHP",
     * "$500 allowance", "$500 monthly allowance". A joining word ("+", "plus", "and", "with", "&")
     * or a heading ("Compensation & Benefits $120k") does not make the amount a benefit.
     */
    private function isBenefit(string $before, string $after): bool
    {
        $word = '(allowances?|benefits?)\b';

        return preg_match('/\b'.$word.'\s+of\s*$/iu', $before) === 1
            || preg_match('/^\s*((?!(plus|and|with)\b)[a-z]+\s+)?'.$word.'/iu', $after) === 1;
    }

    /**
     * Each number with its currency markers, its "k" and its position in bytes.
     *
     * @return list<array{value: float, k: bool, markers: list<string>, start: int, end: int}>
     */
    private function tokens(string $sentence): array
    {
        $codes = implode('|', self::CODES);
        $symbols = '(?<![A-Za-z])(?:AU|NZ|CA|US|HK|SG|A|C|S|R)\$|\$|€|£|¥|₹|₱';
        // A code before a symbol belongs to the same amount: "USD $167,258.30".
        $pattern = '/(?<lead>\b(?:'.$codes.')\s?(?='.$symbols.'))?'
            .'(?<pre>(?:'.$symbols.')|\b(?:'.$codes.')\s?)?'
            .'(?<![\d.,\'’])(?<num>\d{1,3}(?:[.,\'’]\d{3})+(?:[.,]\d{1,2})?|\d+(?:[.,]\d{1,2})?)(?![\d])'
            .'(?<k>\s?[kK]\b)?'
            .'(?<post>\s?(?:'.$codes.')\b)?/u';

        preg_match_all($pattern, $sentence, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL);

        $tokens = [];

        foreach ($matches as $match) {
            if ($match[0][0] === null || $match['num'][0] === null) {
                continue;
            }

            $value = $this->number($match['num'][0]);
            $k = $match['k'][0] !== null;

            if ($value === null) {
                continue;
            }

            $value = $k ? $value * 1000 : $value;
            $pre = trim((string) $match['pre'][0]);

            if ($pre === 'PHP' && $value < self::PHP_VERSION_LIMIT) {
                $pre = '';
            }

            // "PHP" before a symbol is the language: "Senior PHP $120k".
            $lead = trim((string) $match['lead'][0]);
            $lead = $lead === 'PHP' ? '' : $lead;

            $tokens[] = [
                'value' => $value,
                'k' => $k,
                'markers' => array_values(array_filter([$lead, $pre, trim((string) $match['post'][0])])),
                'start' => $match[0][1],
                'end' => $match[0][1] + strlen($match[0][0]),
            ];
        }

        return $tokens;
    }

    /**
     * "160,000.00", "5.000" and "120’000" (Swiss) use thousands separators. "1,5" and "60.50" have decimals.
     */
    private function number(string $text): ?float
    {
        if (preg_match('/^\d{1,3}([.,\'’]\d{3})+([.,]\d{1,2})?$/u', $text) === 1) {
            $decimals = preg_match('/[.,](\d{1,2})$/', $text, $tail) === 1 ? $tail[1] : null;
            $whole = $decimals === null ? $text : substr($text, 0, -strlen($decimals) - 1);

            return (float) (str_replace([',', '.', "'", '’'], '', $whole).($decimals === null ? '' : '.'.$decimals));
        }

        return is_numeric(str_replace(',', '.', $text)) ? (float) str_replace(',', '.', $text) : null;
    }

    /**
     * "60-70k": the "k" of one side applies to a side below 1000 with no "k".
     *
     * @param  array{value: float, k: bool}  $first
     * @param  array{value: float, k: bool}  $second
     * @return array{0: float, 1: float}
     */
    private function applyThousands(array $first, array $second): array
    {
        $min = $first['value'];
        $max = $second['value'];

        if ($second['k'] && ! $first['k'] && $min < 1000) {
            $min *= 1000;
        }

        if ($first['k'] && ! $second['k'] && $max < 1000) {
            $max *= 1000;
        }

        return [$min, $max];
    }

    /**
     * A code or a symbol with one meaning wins over a bare "$" or "¥".
     * Two different currencies give null.
     *
     * @param  list<string>  $markers
     */
    private function currency(array $markers, ?string $hint): ?string
    {
        $explicit = [];
        $generic = [];

        foreach ($markers as $marker) {
            if (array_key_exists($marker, self::GENERIC)) {
                $generic[] = $marker;
            } else {
                $explicit[] = self::SYMBOLS[$marker] ?? $marker;
            }
        }

        $explicit = array_values(array_unique($explicit));

        if (count($explicit) > 1) {
            return null;
        }

        if (count($explicit) === 1) {
            $fits = array_filter($generic, fn (string $symbol) => in_array($explicit[0], self::GENERIC_FITS[$symbol], true));

            return count($fits) === count($generic) ? $explicit[0] : null;
        }

        if (count(array_unique($generic)) > 1) {
            return null;
        }

        return $hint ?? self::GENERIC[$generic[0]];
    }

    private function period(string $sentence): SalaryPeriod
    {
        $found = array_keys(array_filter(self::PERIODS, fn (string $pattern) => preg_match($pattern, $sentence) === 1));

        return count($found) === 1 ? SalaryPeriod::from($found[0]) : SalaryPeriod::Unknown;
    }
}
