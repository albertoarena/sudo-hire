<?php

namespace App\Parsers\Location;

use App\Schema\EligibilityType;
use App\Schema\NormalisedJob;

/**
 * The number and the share of jobs with unknown eligibility, for source_runs
 * (REQUIREMENTS section 9). Pure: no I/O.
 */
final readonly class UnknownEligibilityShare
{
    /**
     * @param  float  $share  from 0 to 1. 0 when there are no jobs
     */
    private function __construct(public int $unknown, public int $total, public float $share) {}

    /**
     * @param  list<NormalisedJob>  $jobs
     */
    public static function of(array $jobs): self
    {
        $unknown = count(array_filter($jobs, fn (NormalisedJob $job) => $job->eligibility->type === EligibilityType::Unknown));
        $total = count($jobs);

        return new self($unknown, $total, $total === 0 ? 0.0 : $unknown / $total);
    }
}
