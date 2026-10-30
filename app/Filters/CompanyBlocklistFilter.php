<?php

namespace App\Filters;

use App\Schema\NormalisedJob;
use App\Settings\Settings;

/**
 * The job's company is not in the blocklist. Case-insensitive, trimmed. Pure: no I/O.
 */
final class CompanyBlocklistFilter
{
    public static function passes(NormalisedJob $job, Settings $settings): bool
    {
        $name = fn (string $company) => mb_strtolower(trim($company));

        return ! in_array($name($job->company), array_map($name, $settings->companyBlocklist), true);
    }
}
