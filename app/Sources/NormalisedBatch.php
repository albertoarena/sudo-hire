<?php

namespace App\Sources;

use App\Schema\NormalisedJob;

final readonly class NormalisedBatch
{
    /**
     * @param  list<NormalisedJob>  $jobs
     * @param  list<string>  $skipReasons
     */
    public function __construct(
        public array $jobs,
        public int $skipped,
        public array $skipReasons,
    ) {}
}
