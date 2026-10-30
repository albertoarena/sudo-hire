<?php

namespace App\Filters;

/**
 * The result of all filters for one job.
 */
final readonly class FilterResult
{
    public bool $passes;

    /**
     * @param  list<FailReason>  $reasons  one for each failing filter
     * @param  bool  $locationNotStated  unknown eligibility in group mode: the "Location not stated" group
     * @param  bool  $salaryUnknown  the salary is missing or not comparable, and the job passes (the "?" badge)
     * @param  bool  $ote  the salary is on-target earnings (the "OTE" badge)
     */
    public function __construct(
        public array $reasons,
        public bool $locationNotStated,
        public bool $salaryUnknown,
        public bool $ote,
    ) {
        $this->passes = $reasons === [];
    }
}
