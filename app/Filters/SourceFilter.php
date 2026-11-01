<?php

namespace App\Filters;

use App\Schema\NormalisedJob;
use App\Settings\Settings;

/**
 * The job's source, or one of its "also on" sources, is not disabled. Pure: no I/O.
 */
final class SourceFilter
{
    /**
     * @param  list<string>  $alsoOn  the keys of the job's "also on" sources
     */
    public static function passes(NormalisedJob $job, Settings $settings, array $alsoOn = []): bool
    {
        return array_diff([$job->source, ...$alsoOn], $settings->disabledSources) !== [];
    }
}
