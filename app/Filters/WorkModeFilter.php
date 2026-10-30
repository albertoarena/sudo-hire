<?php

namespace App\Filters;

use App\Schema\NormalisedJob;
use App\Schema\WorkMode;
use App\Settings\Settings;

/**
 * The job's work mode is in the setting. An unknown work mode always passes. Pure: no I/O.
 */
final class WorkModeFilter
{
    public static function passes(NormalisedJob $job, Settings $settings): bool
    {
        return $job->workMode === WorkMode::Unknown || in_array($job->workMode, $settings->workModes, true);
    }
}
