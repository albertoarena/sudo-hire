<?php

namespace App\Scan;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A scan ended. Listeners update the menu bar badge and the scan times of the status bar.
 */
final readonly class ScanFinished
{
    use Dispatchable;

    public function __construct(
        public ScanResult $result,
        public CarbonImmutable $startedAt,
        public CarbonImmutable $finishedAt,
    ) {}
}
