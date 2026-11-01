<?php

namespace App\Scan;

use App\Filters\FilterResult;
use App\Models\Job;

/**
 * One job that passes the filters, with the links of its first enabled source.
 * The stored links of the job do not change (AGENTS.md rule 11).
 */
final readonly class ScanMatch
{
    public function __construct(
        public Job $job,
        public FilterResult $result,
        public string $source,
        public string $sourceUrl,
        public string $applyUrl,
        public string $attribution,
        public string $salary,
    ) {}
}
