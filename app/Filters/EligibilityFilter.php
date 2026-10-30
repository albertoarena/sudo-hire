<?php

namespace App\Filters;

use App\Parsers\Location\Regions;
use App\Schema\EligibilityType;
use App\Schema\NormalisedJob;
use App\Settings\Settings;
use App\Settings\UnknownEligibilityMode;

/**
 * Can the user do the job from their country? See REQUIREMENTS section 6. Pure: no I/O.
 */
final class EligibilityFilter
{
    public static function outcome(NormalisedJob $job, Settings $settings): EligibilityOutcome
    {
        $eligibility = $job->eligibility;
        $country = $settings->country;

        // No country before the first run: the filter is off.
        if ($country === null || $eligibility->type === EligibilityType::Worldwide) {
            return EligibilityOutcome::Pass;
        }

        if ($eligibility->type === EligibilityType::Unknown) {
            return match ($settings->unknownEligibility) {
                UnknownEligibilityMode::Group => EligibilityOutcome::Group,
                UnknownEligibilityMode::Strict => EligibilityOutcome::Fail,
                UnknownEligibilityMode::Lenient => EligibilityOutcome::Pass,
            };
        }

        $matches = in_array($country, $eligibility->countries, true)
            || array_intersect($eligibility->regions, Regions::of($country)) !== [];

        return $matches ? EligibilityOutcome::Pass : EligibilityOutcome::Fail;
    }
}
