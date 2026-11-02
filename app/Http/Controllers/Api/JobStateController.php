<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\JobStateRequest;
use App\List\JobList;
use App\List\UnseenBadge;
use App\Models\Job;
use App\Storage\JobStore;
use Illuminate\Http\JsonResponse;

/**
 * PUT /api/jobs/{job}/state: save, apply or hide a job. The same state again moves it back to New.
 */
final class JobStateController
{
    public function __invoke(JobStateRequest $request, Job $job, JobStore $store, JobList $list, UnseenBadge $badge): JsonResponse
    {
        $job = $store->setState($job, $request->state());
        $badge->refresh();

        return response()->json([
            'state' => $job->state?->value,
            'counts' => $list->build()->counts(),
        ]);
    }
}
