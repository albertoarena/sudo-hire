<?php

namespace App\Parsers\Location;

use App\Schema\Eligibility;
use App\Schema\EligibilityType;
use App\Schema\WorkMode;

/**
 * Reads a location limit from a job title, e.g. "(Remote Eligible in Bulgaria)"
 * or "Remote, EMEA", and narrows the eligibility of the source with it. Pure: no I/O.
 *
 * Only clear patterns count. See "Location limits in the title" in docs/DECISIONS.md.
 */
final class TitleLocationParser
{
    /** Words that say a place is a limit. A country needs one of them in the title. */
    private const LIMIT_WORDS = '/\b(?:remote(?:ly)?|eligible|only|based)\b/iu';

    /** Separators that start a part after the role name. */
    private const SEPARATORS = '/\s+[-–—|]\s+|,\s+/u';

    /**
     * The places a title limits the job to. A title with no clear limit gives unknown.
     */
    public function parse(string $title): Eligibility
    {
        $limitWord = preg_match(self::LIMIT_WORDS, $title) === 1;
        $regions = [];
        $countries = [];

        foreach ($this->candidates($title) as $candidate) {
            $location = (new LocationParser)->parse((string) preg_replace('/\beligible\b/iu', ' ', $candidate));

            if ($location->eligibility->type !== EligibilityType::Restricted || in_array($location->workMode, [WorkMode::Hybrid, WorkMode::Onsite], true)) {
                continue;
            }

            if ($location->eligibility->countries !== [] && ! $limitWord) {
                continue;
            }

            $regions = array_merge($regions, $location->eligibility->regions);
            $countries = array_merge($countries, $location->eligibility->countries);
        }

        return $regions === [] && $countries === [] ? Eligibility::unknown() : Eligibility::restricted($regions, $countries);
    }

    /**
     * Narrows, never widens. When the title names no place, the source eligibility stays.
     * A worldwide or unknown source takes the title places. Two lists give the places in
     * both. When they have no place in common, the result is unknown, not a refusal.
     */
    public function narrow(Eligibility $source, string $title): Eligibility
    {
        $limit = $this->parse($title);

        if ($limit->type !== EligibilityType::Restricted) {
            return $source;
        }

        if ($source->type !== EligibilityType::Restricted) {
            return $limit;
        }

        $sourceCountries = self::countries($source);
        $limitCountries = self::countries($limit);
        $common = array_values(array_intersect($sourceCountries, $limitCountries));

        if ($common === []) {
            return Eligibility::unknown();
        }

        if ($common === $sourceCountries) {
            return $source;
        }

        if (array_diff($limitCountries, $common) === []) {
            return $limit;
        }

        return Eligibility::restricted([], $common);
    }

    /**
     * The text in brackets, and each part after the role name.
     * "Senior Engineer, Payments - EU (Remote)" gives "Remote", "Payments" and "EU".
     *
     * @return list<string>
     */
    private function candidates(string $title): array
    {
        preg_match_all('/\(([^()]*)\)|\[([^\[\]]*)\]/u', $title, $matches);
        $brackets = array_map(fn (string $a, string $b) => $a.$b, $matches[1], $matches[2]);

        $outside = (string) preg_replace('/\([^()]*\)|\[[^\[\]]*\]/u', ' ', $title);
        $parts = array_slice(preg_split(self::SEPARATORS, $outside) ?: [], 1);

        return array_values(array_filter(
            array_map(fn (string $part) => Text::clean($part), [...$brackets, ...$parts]),
            fn (string $part) => $part !== '',
        ));
    }

    /**
     * Every country code the eligibility covers, the listed countries first.
     *
     * @return list<string>
     */
    private static function countries(Eligibility $eligibility): array
    {
        $codes = $eligibility->countries;

        foreach (Regions::countries() as $code) {
            if (array_intersect($eligibility->regions, Regions::of($code)) !== []) {
                $codes[] = $code;
            }
        }

        return array_values(array_unique($codes));
    }
}
