<?php

namespace App\Http\Controllers\Api;

use App\List\JobList;
use Illuminate\Http\JsonResponse;

/**
 * GET /api/list: what the menu bar list shows.
 */
final class ListController
{
    public function __invoke(JobList $list): JsonResponse
    {
        return response()->json($list->build()->toArray());
    }
}
