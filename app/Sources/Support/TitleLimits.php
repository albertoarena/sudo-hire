<?php

namespace App\Sources\Support;

use App\Parsers\Location\TitleLocationParser;
use App\Schema\Eligibility;
use App\Schema\WorkMode;

/**
 * The one place where every source narrows its eligibility with the title.
 * Hybrid and on-site jobs keep unknown: their places are offices.
 */
final class TitleLimits
{
    public static function eligibility(Eligibility $source, WorkMode $workMode, string $title): Eligibility
    {
        if (in_array($workMode, [WorkMode::Hybrid, WorkMode::Onsite], true)) {
            return $source;
        }

        return (new TitleLocationParser)->narrow($source, $title);
    }
}
