<?php

namespace App\Filters;

use App\Schema\NormalisedJob;
use App\Settings\Settings;

/**
 * The job's source is not disabled. Pure: no I/O.
 */
final class SourceFilter
{
    public static function passes(NormalisedJob $job, Settings $settings): bool
    {
        return ! in_array($job->source, $settings->disabledSources, true);
    }
}
