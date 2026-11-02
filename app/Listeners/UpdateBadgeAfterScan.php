<?php

namespace App\Listeners;

use App\List\UnseenBadge;
use App\Scan\ScanFinished;

/**
 * Updates the menu bar badge after each scan.
 */
final class UpdateBadgeAfterScan
{
    public function __construct(private readonly UnseenBadge $badge) {}

    public function handle(ScanFinished $event): void
    {
        $this->badge->refresh();
    }
}
