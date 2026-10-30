<?php

namespace App\Storage;

use App\Models\AlsoOnSource;
use App\Models\Job;
use App\Schema\NormalisedJob;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Stores normalised jobs with deduplication and retention (REQUIREMENTS section 9).
 * A job keeps its own links. Another source of the same job is stored as "also on",
 * with its own links: an apply link is never replaced (AGENTS.md rule 11).
 */
final class JobStore
{
    /** A job from another source with the same fingerprint, stored this recently, is the same job. */
    public const int DUPLICATE_DAYS = 14;

    /** A job not seen for this long is deleted. */
    public const int RETENTION_DAYS = 30;

    /**
     * @param  list<NormalisedJob>  $jobs
     */
    public function save(array $jobs): void
    {
        DB::transaction(function () use ($jobs) {
            foreach ($jobs as $job) {
                $this->saveOne($job);
            }
        });
    }

    /**
     * The jobs that were never in a notification. M6c and M7 set notified_at.
     *
     * @return Collection<int, Job>
     */
    public function notNotified(): Collection
    {
        return Job::query()->whereNull('notified_at')->orderBy('id')->get();
    }

    /**
     * Deletes the jobs not seen for RETENTION_DAYS, with their "also on" rows.
     *
     * @return int the number of deleted jobs
     */
    public function prune(): int
    {
        $old = Job::query()->where('fetched_at', '<', CarbonImmutable::now()->subDays(self::RETENTION_DAYS));

        return DB::transaction(function () use ($old) {
            AlsoOnSource::query()->whereIn('job_id', (clone $old)->select('id'))->delete();

            return $old->delete();
        });
    }

    private function saveOne(NormalisedJob $job): void
    {
        $stored = Job::query()->where('source', $job->source)->where('source_job_id', $job->sourceJobId)->first();

        if ($stored !== null) {
            $stored->update(Job::attributesOf($job));

            return;
        }

        $alsoOn = AlsoOnSource::query()->where('source', $job->source)->where('source_job_id', $job->sourceJobId)->first();

        if ($alsoOn !== null) {
            $alsoOn->update($this->links($job));
            $this->seen($alsoOn->job_id, $job->fetchedAt);

            return;
        }

        $first = Job::query()
            ->where('fingerprint', $job->fingerprint)
            ->where('source', '!=', $job->source)
            ->where('created_at', '>=', CarbonImmutable::now()->subDays(self::DUPLICATE_DAYS))
            ->orderBy('id')
            ->first();

        if ($first !== null) {
            AlsoOnSource::query()->create(['job_id' => $first->id, 'source' => $job->source, 'source_job_id' => $job->sourceJobId] + $this->links($job));
            $this->seen($first->id, $job->fetchedAt);

            return;
        }

        Job::query()->create(Job::attributesOf($job));
    }

    /**
     * @return array<string, mixed>
     */
    private function links(NormalisedJob $job): array
    {
        return [
            'source_url' => $job->sourceUrl,
            'apply_url' => $job->applyUrl,
            'attribution' => $job->attribution,
            'fetched_at' => $job->fetchedAt,
        ];
    }

    /**
     * The job was seen through another source: retention counts from this fetch.
     */
    private function seen(int $jobId, CarbonImmutable $fetchedAt): void
    {
        Job::query()->whereKey($jobId)->where('fetched_at', '<', $fetchedAt)->update(['fetched_at' => $fetchedAt]);
    }
}
