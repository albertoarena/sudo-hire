<?php

namespace App\Filters;

use App\Schema\NormalisedJob;
use App\Schema\Seniority;
use App\Settings\Settings;

/**
 * The job's seniority is in the setting. An unknown seniority always passes. Pure: no I/O.
 */
final class SeniorityFilter
{
    public static function passes(NormalisedJob $job, Settings $settings): bool
    {
        return $job->seniority === Seniority::Unknown || in_array($job->seniority, $settings->seniorities, true);
    }
}
