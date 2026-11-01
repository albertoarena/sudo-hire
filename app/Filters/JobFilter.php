<?php

namespace App\Filters;

use App\Currency\CurrencyConverter;
use App\Schema\NormalisedJob;
use App\Settings\Settings;
use Carbon\CarbonImmutable;

/**
 * Runs every filter of REQUIREMENTS section 8 on one job. Pure: no I/O.
 * A job in the "Location not stated" group passes: M6c does not count it.
 */
final class JobFilter
{
    /**
     * @param  list<string>  $alsoOn  the keys of the job's "also on" sources, for the source filter
     */
    public static function evaluate(NormalisedJob $job, Settings $settings, CurrencyConverter $converter, CarbonImmutable $now, array $alsoOn = []): FilterResult
    {
        $eligibility = EligibilityFilter::outcome($job, $settings);
        $salary = SalaryFilter::check($job, $settings, $converter);

        $checks = [
            FailReason::WorkMode->value => WorkModeFilter::passes($job, $settings),
            FailReason::Eligibility->value => $eligibility !== EligibilityOutcome::Fail,
            FailReason::Timezone->value => TimezoneFilter::passes($job, $settings, $now),
            FailReason::KeywordMissing->value => KeywordFilter::passesInclude($job, $settings),
            FailReason::KeywordExcluded->value => KeywordFilter::passesExclude($job, $settings),
            FailReason::Salary->value => $salary->passes,
            FailReason::ContractType->value => ContractTypeFilter::passes($job, $settings),
            FailReason::Seniority->value => SeniorityFilter::passes($job, $settings),
            FailReason::PostedWithin->value => PostedWithinFilter::passes($job, $settings, $now),
            FailReason::Company->value => CompanyBlocklistFilter::passes($job, $settings),
            FailReason::Source->value => SourceFilter::passes($job, $settings, $alsoOn),
            FailReason::Expired->value => ExpiryFilter::passes($job, $now),
        ];

        $reasons = [];

        foreach ($checks as $reason => $passes) {
            if (! $passes) {
                $reasons[] = FailReason::from($reason);
            }
        }

        return new FilterResult($reasons, $eligibility === EligibilityOutcome::Group, $salary->unknown, $salary->ote);
    }
}
