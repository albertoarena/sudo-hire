<?php

namespace App\Parsers\Location;

use App\Schema\Eligibility;
use App\Schema\WorkMode;

/**
 * Reads a free text location into eligibility, work mode, timezone offsets
 * and office location. REQUIREMENTS section 6. Pure: no I/O.
 *
 * Text the rules cannot read gives unknown, never a guess.
 */
final class LocationParser
{
    /** @var array<string, string> work mode to the words that say it */
    private const MODES = [
        'remote' => '/\b(?:fully\s+remote|100%\s*remote|remote(?:ly)?|distributed|work\s+from\s+home|wfh)\b/iu',
        'hybrid' => '/\bhybrid\b/iu',
        'onsite' => '/\b(?:on[\s-]?site|in[\s-]office|office[\s-]based)\b/iu',
    ];

    private const WORLDWIDE = '/\b(?:anywhere|worldwide|world\s*wide|globally|global)\b/iu';

    private const WORLD_WORDS = '/\b(?:anywhere|worldwide|world\s*wide|globally|global|in\s+the\s+world|the\s+world|world)\b/iu';

    /** Text that excludes places: the parser does not read exclusions. */
    private const NEGATION = '/\b(?:except|excluding|exclude|not|outside|no)\b/iu';

    /** Words around a place name, e.g. "US only", "based in Spain". */
    private const FILLER = '/^(?:(?:only|based|in|from|within|residents?|citizens|located|candidates|applicants|must|be|open|to|all)\b\s*)+|(?:\s*\b(?:only|based|residents?|citizens|candidates|applicants|to|in))+$/iu';

    /** Country names that are also US states. Free text does not read them: "Atlanta, Georgia". */
    private const AMBIGUOUS = ['georgia'];

    public function parse(string $text): ParsedLocation
    {
        $parts = array_map(fn (string $part) => $this->part($part), explode(';', $text));

        $modes = array_map(fn (array $part) => $part['mode'], $parts);
        $mode = WorkMode::Unknown;

        foreach ([WorkMode::Remote, WorkMode::Hybrid, WorkMode::Onsite] as $candidate) {
            if (in_array($candidate, $modes, true)) {
                $mode = $candidate;
                break;
            }
        }

        $offsets = array_filter(array_merge(...array_map(fn (array $part) => [$part['min'], $part['max']], $parts)), fn (?float $offset) => $offset !== null);

        return new ParsedLocation(
            eligibility: $this->merge($parts, $mode),
            workMode: $mode,
            timezoneMin: $offsets === [] ? null : min($offsets),
            timezoneMax: $offsets === [] ? null : max($offsets),
            officeLocation: $this->office($parts, $mode),
        );
    }

    /**
     * Country or region names, e.g. Himalayas `locationRestrictions`. A name that is not
     * in the tables is skipped, so one unknown spelling does not lose the rest of the list.
     * A list with no known name gives unknown. An empty list gives unknown: the source decides.
     *
     * @param  list<string>  $names
     */
    public function countryList(array $names): Eligibility
    {
        $regions = [];
        $countries = [];

        foreach ($names as $name) {
            $name = Text::clean(Text::stripEmoji($name));

            if (($region = Regions::key($name)) !== null) {
                $regions[] = $region;
            } elseif (($country = Countries::code($name)) !== null) {
                $countries[] = $country;
            }
        }

        return $regions === [] && $countries === [] ? Eligibility::unknown() : Eligibility::restricted($regions, $countries);
    }

    /**
     * A comma list of country names, e.g. We Work Remotely `country`:
     * "🇦🇩 Andorra, 🇦🇱 Albania, and 🇺🇦 Ukraine".
     */
    public function countryText(string $text): Eligibility
    {
        $names = array_map(
            fn (string $name) => (string) preg_replace('/^and\s+/iu', '', Text::clean(Text::stripEmoji($name))),
            explode(',', $text),
        );

        return $this->countryList(array_values(array_filter($names, fn (string $name) => $name !== '')));
    }

    /**
     * @return array{mode: WorkMode, regions: list<string>, countries: list<string>, worldwide: bool, office: string|null, min: float|null, max: float|null}
     */
    private function part(string $text): array
    {
        [$text, $min, $max] = Timezones::extract(Text::stripEmoji($text));

        $mode = WorkMode::Unknown;

        foreach (array_reverse(self::MODES, true) as $value => $pattern) {
            if (preg_match($pattern, $text) === 1) {
                $mode = WorkMode::from($value);
                $text = (string) preg_replace($pattern, ' ', $text);
            }
        }

        $negated = preg_match(self::NEGATION, $text) === 1;
        $regions = [];
        $countries = [];
        $worldwide = false;

        foreach ($this->tokens(Text::clean($text)) as $token) {
            $place = $this->place($token);
            $worldwide = $worldwide || $place['worldwide'];
            $regions = array_merge($regions, $place['regions']);
            $countries = array_merge($countries, $place['countries']);
        }

        if ($mode === WorkMode::Unknown && $worldwide) {
            $mode = WorkMode::Remote;
        }

        if ($negated) {
            [$regions, $countries, $worldwide] = [[], [], false];
        }

        $office = in_array($mode, [WorkMode::Hybrid, WorkMode::Onsite], true) ? Text::clean($text) : '';

        return [
            'mode' => $mode,
            'regions' => $regions,
            'countries' => $countries,
            'worldwide' => $worldwide,
            'office' => $office === '' ? null : $office,
            'min' => $min,
            'max' => $max,
        ];
    }

    /**
     * Splits at commas first. A piece that is not a place is split again at
     * " - ", "/", "&", brackets, "and" and "or". "Bosnia and Herzegovina" stays one piece.
     *
     * @return list<string>
     */
    private function tokens(string $text): array
    {
        $tokens = [];

        foreach (preg_split('/[,|]/u', $text) ?: [] as $piece) {
            $piece = (string) preg_replace('/^(?:and|or)\s+/iu', '', Text::clean($piece));

            if ($piece === '') {
                continue;
            }

            if ($this->place($piece)['read']) {
                $tokens[] = $piece;

                continue;
            }

            foreach (preg_split('/\s+(?:-|–|—|and|or)\s+|[\/&()]/iu', $piece) ?: [] as $part) {
                if (($part = Text::clean($part)) !== '') {
                    $tokens[] = $part;
                }
            }
        }

        return $tokens;
    }

    /**
     * @return array{read: bool, worldwide: bool, regions: list<string>, countries: list<string>}
     */
    private function place(string $token): array
    {
        $worldwide = preg_match(self::WORLDWIDE, $token) === 1;
        $rest = Text::clean((string) preg_replace(self::FILLER, '', Text::clean((string) preg_replace(self::WORLD_WORDS, ' ', $token))));

        $result = ['read' => $worldwide, 'worldwide' => $worldwide, 'regions' => [], 'countries' => []];

        if ($rest === '') {
            return $result;
        }

        if (($region = Regions::key($rest)) !== null) {
            return ['read' => true, 'regions' => [$region]] + $result;
        }

        if (($country = Countries::code($rest)) !== null && ! in_array(Countries::normalise($rest), self::AMBIGUOUS, true)) {
            return ['read' => true, 'countries' => [$country]] + $result;
        }

        return ['read' => false, 'worldwide' => false] + $result;
    }

    /**
     * Places win over worldwide words. When the job is remote, only the remote parts count.
     * Hybrid and on-site jobs give unknown: their places are offices, not eligibility.
     *
     * @param  list<array{mode: WorkMode, regions: list<string>, countries: list<string>, worldwide: bool, office: string|null, min: float|null, max: float|null}>  $parts
     */
    private function merge(array $parts, WorkMode $mode): Eligibility
    {
        if (in_array($mode, [WorkMode::Hybrid, WorkMode::Onsite], true)) {
            return Eligibility::unknown();
        }

        $parts = array_filter($parts, fn (array $part) => $part['mode'] === $mode);
        $regions = array_merge([], ...array_column($parts, 'regions'));
        $countries = array_merge([], ...array_column($parts, 'countries'));

        if ($regions !== [] || $countries !== []) {
            return Eligibility::restricted($regions, $countries);
        }

        return in_array(true, array_column($parts, 'worldwide'), true) ? Eligibility::worldwide() : Eligibility::unknown();
    }

    /**
     * @param  list<array{mode: WorkMode, office: string|null}>  $parts
     */
    private function office(array $parts, WorkMode $mode): ?string
    {
        if (! in_array($mode, [WorkMode::Hybrid, WorkMode::Onsite], true)) {
            return null;
        }

        $offices = array_filter(array_column($parts, 'office'), fn (?string $office) => $office !== null);

        return $offices === [] ? null : implode('; ', $offices);
    }
}
