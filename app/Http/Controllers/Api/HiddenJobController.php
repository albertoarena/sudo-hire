<?php

namespace App\Http\Controllers\Api;

use App\List\JobState;
use App\Models\HiddenJob;
use App\Models\Job;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * The hidden jobs of the Jobs pane. "Show again" uses the state endpoint.
 * An older hidden job is one that retention deleted: only its tombstones stay.
 */
final class HiddenJobController
{
    public function index(): JsonResponse
    {
        $jobs = Job::query()
            ->where('state', JobState::Hidden)
            ->orderByDesc('state_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Job $job) => [
                'id' => $job->id,
                'title' => $job->title,
                'company' => $job->company,
                'hiddenAt' => $job->state_at?->utc()->toIso8601ZuluString(),
            ]);

        return response()->json([
            'jobs' => $jobs,
            'olderCount' => $this->older()->distinct()->count('job_id'),
        ]);
    }

    /**
     * Deletes the tombstones of the deleted jobs. A source that still lists one of these jobs can store it again.
     */
    public function destroyOlder(): Response
    {
        $this->older()->delete();

        return response()->noContent();
    }

    /**
     * @return Builder<HiddenJob>
     */
    private function older(): Builder
    {
        return HiddenJob::query()->whereNotIn('job_id', Job::query()->select('id'));
    }
}
