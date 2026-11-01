<?php

namespace App\Scan;

use App\Models\SentNotification;
use App\Models\SourceRun;
use Carbon\CarbonImmutable;

/**
 * What one scan did.
 */
final readonly class ScanResult
{
    /**
     * @param  list<SourceRun>  $runs  the source runs of this scan
     * @param  list<ScanMatch>  $matches  new matches, without the "Location not stated" group
     * @param  int  $locationNotStated  new jobs in the "Location not stated" group
     * @param  NotificationOutcome  $notification  what happened to the summary notification
     * @param  SentNotification|null  $sent  the notification sent by this scan
     * @param  CarbonImmutable|null  $lastSentAt  the last notification before this scan
     * @param  string|null  $notificationError  why the notifier failed
     */
    public function __construct(
        public array $runs,
        public array $matches,
        public int $locationNotStated,
        public NotificationOutcome $notification,
        public ?SentNotification $sent = null,
        public ?CarbonImmutable $lastSentAt = null,
        public ?string $notificationError = null,
    ) {}
}
