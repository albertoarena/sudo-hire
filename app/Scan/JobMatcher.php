<?php

namespace App\Scan;

use App\Currency\CurrencyConverter;
use App\Filters\JobFilter;
use App\Models\AlsoOnSource;
use App\Models\Job;
use App\Settings\Settings;
use Carbon\CarbonImmutable;

/**
 * Runs the filters on one stored job and picks the links of its first enabled source:
 * the main source, else the first "also on" source in stored order. When no source is
 * enabled, the main source. The scan and the list share it. The job needs alsoOn loaded.
 */
final class JobMatcher
{
    public static function evaluate(Job $job, Settings $settings, CurrencyConverter $converter, CarbonImmutable $now): ScanMatch
    {
        $alsoOn = array_values($job->alsoOn->sortBy('id')->all());
        $normalised = $job->toNormalised();
        $result = JobFilter::evaluate($normalised, $settings, $converter, $now, array_map(fn (AlsoOnSource $other) => $other->source, $alsoOn));
        $salary = SalaryText::of($normalised->salary, $result, $converter, $settings->displayCurrency);

        if (in_array($job->source, $settings->disabledSources, true)) {
            foreach ($alsoOn as $other) {
                if (! in_array($other->source, $settings->disabledSources, true)) {
                    return new ScanMatch($job, $result, $other->source, $other->source_url, $other->apply_url, $other->attribution, $salary);
                }
            }
        }

        return new ScanMatch($job, $result, $job->source, $job->source_url, $job->apply_url, $job->attribution, $salary);
    }
}
