<?php

namespace App\List;

use App\Filters\FailReason;
use App\Filters\FilterResult;
use App\Parsers\Location\Regions;
use App\Schema\EligibilityType;
use App\Schema\NormalisedJob;
use App\Settings\Settings;

/**
 * The entry stamp of a job row (DESIGN.md "The entry stamp"): the kind and the place.
 * For an admitted job, the place is the name of the first region of the job that admits
 * the user, unless the job lists the user's country. Else it is the user's country code.
 * With no country, it is the job's own place: its first listed country code, else the name
 * of its first region, or "Anywhere" for a worldwide job. "Location" is only for the
 * not_stated kind. Pure: no I/O.
 */
final readonly class Stamp
{
    public const string ADMITTED = 'admitted';

    public const string NOT_STATED = 'not_stated';

    /** Only for a saved or applied job that the filters refuse now. */
    public const string REFUSED = 'refused';

    public const string LOCATION = 'Location';

    public const string ANYWHERE = 'Anywhere';

    /** @var array<string, string> region key to the name on the stamp */
    private const array NAMES = [
        'africa' => 'Africa',
        'americas' => 'Americas',
        'apac' => 'APAC',
        'asia' => 'Asia',
        'emea' => 'EMEA',
        'eu' => 'EU',
        'europe' => 'Europe',
        'latam' => 'LATAM',
        'middle_east' => 'Middle East',
        'north_america' => 'North America',
        'oceania' => 'Oceania',
    ];

    private function __construct(public string $kind, public ?string $place) {}

    public static function of(NormalisedJob $job, Settings $settings, FilterResult $result): self
    {
        $eligibility = $job->eligibility;
        $country = $settings->country;

        if ($eligibility->type === EligibilityType::Unknown) {
            return new self(self::NOT_STATED, self::LOCATION);
        }

        if (in_array(FailReason::Eligibility, $result->reasons, true)) {
            return new self(self::REFUSED, null);
        }

        if ($country === null) {
            return new self(self::ADMITTED, self::placeOf($job));
        }

        if ($eligibility->type === EligibilityType::Restricted && ! in_array($country, $eligibility->countries, true)) {
            $userRegions = Regions::of($country);

            foreach ($eligibility->regions as $region) {
                if (in_array($region, $userRegions, true) && isset(self::NAMES[$region])) {
                    return new self(self::ADMITTED, self::NAMES[$region]);
                }
            }
        }

        // The job lists the country, is open worldwide, or has no region name: the user's country.
        return new self(self::ADMITTED, $country);
    }

    /**
     * The job's own place, for a user with no country.
     */
    private static function placeOf(NormalisedJob $job): string
    {
        $eligibility = $job->eligibility;

        if ($eligibility->countries !== []) {
            return $eligibility->countries[0];
        }

        if ($eligibility->regions !== []) {
            return self::name($eligibility->regions[0]);
        }

        return self::ANYWHERE;
    }

    /**
     * The stamp name of a region key: "northern_europe" with no entry gives "Northern Europe".
     */
    private static function name(string $region): string
    {
        return self::NAMES[$region] ?? ucwords(str_replace('_', ' ', $region));
    }

    /**
     * @return array{kind: string, place: string|null}
     */
    public function toArray(): array
    {
        return ['kind' => $this->kind, 'place' => $this->place];
    }
}
