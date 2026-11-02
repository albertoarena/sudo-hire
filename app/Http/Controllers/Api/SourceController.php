<?php

namespace App\Http\Controllers\Api;

use App\List\SourceStatuses;
use Illuminate\Http\JsonResponse;

/**
 * GET /api/sources: each source with its status.
 */
final class SourceController
{
    public function __invoke(SourceStatuses $statuses): JsonResponse
    {
        return response()->json($statuses->all());
    }
}
