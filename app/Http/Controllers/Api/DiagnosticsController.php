<?php

namespace App\Http\Controllers\Api;

use App\List\DiagnosticReport;
use Illuminate\Http\JsonResponse;

/**
 * GET /api/diagnostics: the report for "Copy diagnostic report", as JSON.
 */
final class DiagnosticsController
{
    public function __invoke(DiagnosticReport $report): JsonResponse
    {
        return response()->json($report->toArray());
    }
}
