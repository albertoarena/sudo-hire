<?php

namespace App\Filters;

use App\Schema\NormalisedJob;
use App\Settings\Settings;
use Carbon\CarbonImmutable;
use DateTimeZone;

/**
 * The job's timezones overlap the user's UTC offset ± the hours of the setting.
 * Offsets are on a 24-hour circle, so UTC+14 is UTC-10. Pure: no I/O.
 */
final class TimezoneFilter
{
    public static function passes(NormalisedJob $job, Settings $settings, CarbonImmutable $now): bool
    {
        $hours = $settings->timezoneOverlapHours;
        $min = $job->timezoneMin ?? $job->timezoneMax;
        $max = $job->timezoneMax ?? $job->timezoneMin;

        if ($hours === null || $min === null || $max === null) {
            return true;
        }

        $offset = (new DateTimeZone($settings->timezone))->getOffset($now) / 3600;

        foreach ([-24, 0, 24] as $shift) {
            if ($min <= $offset + $hours + $shift && $max >= $offset - $hours + $shift) {
                return true;
            }
        }

        return false;
    }
}
