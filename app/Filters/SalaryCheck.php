<?php

namespace App\Filters;

/**
 * The result of the salary filter.
 */
final readonly class SalaryCheck
{
    /**
     * @param  bool  $unknown  the salary is missing or not comparable, and the job passes (the "?" badge)
     * @param  bool  $ote  the salary is on-target earnings (the "OTE" badge)
     */
    public function __construct(
        public bool $passes,
        public bool $unknown,
        public bool $ote,
    ) {}
}
