<?php

namespace App\Filters;

use App\Schema\NormalisedJob;
use App\Settings\Settings;
use Carbon\CarbonImmutable;

/**
 * The job was posted within the hours of the setting. Pure: no I/O.
 */
final class PostedWithinFilter
{
    public static function passes(NormalisedJob $job, Settings $settings, CarbonImmutable $now): bool
    {
        return $job->postedAt->gte($now->subHours($settings->postedWithinHours));
    }
}
