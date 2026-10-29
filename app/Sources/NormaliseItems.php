<?php

namespace App\Sources;

use Carbon\CarbonInterface;

/**
 * Fetches a source and normalises each item. An item that throws SkipItem
 * is counted and the others go on.
 */
final class NormaliseItems
{
    public function __invoke(JobSource $source, ?CarbonInterface $since = null): NormalisedBatch
    {
        $jobs = [];
        $skipReasons = [];

        foreach ($source->fetch($since) as $raw) {
            try {
                $jobs[] = $source->normalise($raw);
            } catch (SkipItem $skip) {
                $skipReasons[] = $skip->getMessage();
            }
        }

        return new NormalisedBatch($jobs, count($skipReasons), $skipReasons);
    }
}
