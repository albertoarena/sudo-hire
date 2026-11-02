<?php

namespace App\Listeners;

use App\List\LastScan;
use App\Scan\ScanFinished;

/**
 * Stores the start and end of the last scan, for the status bar.
 */
final class RecordScanTimes
{
    public function __construct(private readonly LastScan $lastScan) {}

    public function handle(ScanFinished $event): void
    {
        $this->lastScan->record($event->startedAt, $event->finishedAt);
    }
}
