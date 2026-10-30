<?php

namespace App\Filters;

use App\Schema\NormalisedJob;
use Carbon\CarbonImmutable;

/**
 * The job has not expired. A job with no expiry date passes. Pure: no I/O.
 */
final class ExpiryFilter
{
    public static function passes(NormalisedJob $job, CarbonImmutable $now): bool
    {
        return $job->expiresAt === null || $job->expiresAt->gte($now);
    }
}
