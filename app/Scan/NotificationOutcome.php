<?php

namespace App\Scan;

/**
 * What a scan did with the summary notification.
 */
enum NotificationOutcome: string
{
    case Sent = 'sent';
    case NoMatches = 'no_matches';
    case NotDue = 'not_due';
    case Off = 'off';
    case Failed = 'failed';
    case NoCountry = 'no_country';
    case FirstScan = 'first_scan';
}
