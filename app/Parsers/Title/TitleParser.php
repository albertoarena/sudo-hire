<?php

namespace App\Parsers\Title;

use App\Schema\ContractType;
use App\Schema\Seniority;

/**
 * Reads the seniority and the contract type from one job title. Pure: no I/O.
 *
 * Words match as whole words, case-insensitive. A title with no word
 * from the lists gives unknown, never a guess.
 */
final class TitleParser
{
    /**
     * Levels from lowest to highest. When a title has two levels, the lowest wins.
     *
     * @var array<string, string> seniority to the words that say it
     */
    private const SENIORITY = [
        'junior' => '/\b(?:junior|jr|entry\s+level|graduate|intern)\b/iu',
        'mid' => '/\b(?:mid|intermediate)\b/iu',
        'senior' => '/\b(?:senior|sr)\b/iu',
        'lead' => '/\b(?:lead|principal|staff|head\s+of|manager|director|vp)\b/iu',
    ];

    /** @var array<string, string> contract type to the words that say it */
    private const CONTRACT_TYPES = [
        'contract' => '/\b(?:contract|contractor|freelance)\b/iu',
        'part_time' => '/\bpart[\s-]?time\b/iu',
        'full_time' => '/\bfull[\s-]?time\b/iu',
    ];

    public function parse(string $title): ParsedTitle
    {
        return new ParsedTitle(
            seniority: Seniority::from($this->first(self::SENIORITY, $title)),
            contractType: ContractType::from($this->first(self::CONTRACT_TYPES, $title)),
        );
    }

    /**
     * @param  array<string, string>  $patterns
     */
    private function first(array $patterns, string $title): string
    {
        foreach ($patterns as $value => $pattern) {
            if (preg_match($pattern, $title) === 1) {
                return $value;
            }
        }

        return 'unknown';
    }
}
