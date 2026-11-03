<?php

namespace App\Http\Controllers\Api;

use App\Scan\FirstScanProgress;
use App\Settings\SettingsStore;
use Illuminate\Http\JsonResponse;
use Native\Desktop\ChildProcess;

/**
 * The first scan of the first run. POST starts hire:scan as a NativePHP child process and
 * returns at once: a scan can take a minute. hire:scan is isolated, so it never runs at the
 * same time as the scheduled scan. GET gives the progress of each enabled source.
 */
final class FirstScanController
{
    public function store(SettingsStore $settings, FirstScanProgress $progress, ChildProcess $processes): JsonResponse
    {
        if ($settings->load()->country === null) {
            return response()->json(['message' => 'Choose your country first.'], 422);
        }

        $progress->start();
        $processes->artisan(['hire:scan'], 'first-scan');

        return response()->json($progress->toArray(), 202);
    }

    public function show(FirstScanProgress $progress): JsonResponse
    {
        return response()->json($progress->toArray());
    }
}
