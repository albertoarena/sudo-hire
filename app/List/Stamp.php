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
 * The place is the user's country code when the job lists it, else the name of the first
 * region of the job that admits the user, else "Location". Pure: no I/O.
 */
final readonly class Stamp
{
    public const string ADMITTED = 'admitted';

    public const string NOT_STATED = 'not_stated';

    /** Only for a saved or applied job that the filters refuse now. */
    public const string REFUSED = 'refused';

    public const string LOCATION = 'Location';

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

        if ($country === null || $eligibility->type === EligibilityType::Worldwide) {
            return new self(self::ADMITTED, self::LOCATION);
        }

        if (in_array($country, $eligibility->countries, true)) {
            return new self(self::ADMITTED, $country);
        }

        $userRegions = Regions::of($country);

        foreach ($eligibility->regions as $region) {
            if (in_array($region, $userRegions, true)) {
                return new self(self::ADMITTED, self::NAMES[$region] ?? self::LOCATION);
            }
        }

        return new self(self::ADMITTED, self::LOCATION);
    }

    /**
     * @return array{kind: string, place: string|null}
     */
    public function toArray(): array
    {
        return ['kind' => $this->kind, 'place' => $this->place];
    }
}
