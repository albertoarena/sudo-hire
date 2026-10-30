<?php

namespace App\Filters;

use App\Schema\ContractType;
use App\Schema\NormalisedJob;
use App\Settings\Settings;

/**
 * The job's contract type is in the setting. An unknown contract type always passes. Pure: no I/O.
 */
final class ContractTypeFilter
{
    public static function passes(NormalisedJob $job, Settings $settings): bool
    {
        return $job->contractType === ContractType::Unknown || in_array($job->contractType, $settings->contractTypes, true);
    }
}
