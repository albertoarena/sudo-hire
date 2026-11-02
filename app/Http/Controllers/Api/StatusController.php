<?php

namespace App\Http\Controllers\Api;

use App\List\LastScan;
use App\Models\SentNotification;
use Illuminate\Http\JsonResponse;

/**
 * GET /api/status: the last scan, the next scan and the last sent notification, for the status bar.
 */
final class StatusController
{
    public function __invoke(LastScan $lastScan): JsonResponse
    {
        $scan = $lastScan->toArray();
        $sent = SentNotification::query()->latest('sent_at')->latest('id')->first();

        return response()->json([
            'lastScan' => ['startedAt' => $scan['startedAt'], 'finishedAt' => $scan['finishedAt']],
            'nextScanAt' => $scan['nextAt'],
            'lastNotification' => $sent === null ? null : [
                'sentAt' => $sent->sent_at->utc()->toIso8601ZuluString(),
                'text' => $sent->text,
                'jobCount' => $sent->job_count,
            ],
        ]);
    }
}
