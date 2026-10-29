<?php

namespace App\Sources;

use App\Schema\NormalisedJob;
use Carbon\CarbonInterface;
use Carbon\CarbonInterval;

/**
 * One job board, ATS or remote feed. Changes need approval: premium
 * sources depend on it (AGENTS.md rule 10).
 */
interface JobSource
{
    /**
     * Stable key, e.g. "larajobs". Stored as the job's source.
     */
    public function key(): string;

    /**
     * Raw items as the source returns them. A source with paging or a daily
     * call limit uses $since to stop early.
     *
     * @return iterable<mixed>
     */
    public function fetch(?CarbonInterface $since = null): iterable;

    /**
     * @throws SkipItem when this one item cannot be normalised
     */
    public function normalise(mixed $raw): NormalisedJob;

    /**
     * Text shown next to each job, e.g. "via LaraJobs".
     */
    public function attribution(): string;

    /**
     * Never fetch this source more often than this.
     */
    public function minInterval(): CarbonInterval;
}
