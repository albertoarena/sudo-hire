<?php

namespace App\Storage;

use App\List\JobState;
use App\Models\AlsoOnSource;
use App\Models\HiddenJob;
use App\Models\Job;
use App\Schema\NormalisedJob;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Stores normalised jobs with deduplication and retention (REQUIREMENTS section 9).
 * A job keeps its own links. Another source of the same job, or an identical copy on
 * the same source, is stored as a "job_sources" row with its own links: an apply link
 * is never replaced (AGENTS.md rule 11).
 * A hidden job leaves tombstones, so it does not come back.
 */
final class JobStore
{
    /** A job with the same fingerprint, stored this recently, is the same job (from the same source only when identical). */
    public const int DUPLICATE_DAYS = 14;

    /** A job not seen for this long is deleted, unless it is saved or applied. */
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
     * The new jobs that were never in a notification. M6c and M7 set notified_at.
     * A job that the user saved, applied, hid or saw in the list is not new.
     *
     * @return Collection<int, Job>
     */
    public function notNotified(): Collection
    {
        return Job::query()
            ->whereNull('notified_at')
            ->whereNull('state')
            ->whereNull('seen_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * Deletes the jobs not seen for RETENTION_DAYS, with their "also on" rows.
     * Saved and applied jobs stay. Hidden jobs are deleted, their tombstones stay.
     *
     * @return int the number of deleted jobs
     */
    public function prune(): int
    {
        $old = Job::query()
            ->where('fetched_at', '<', CarbonImmutable::now()->subDays(self::RETENTION_DAYS))
            ->where(fn ($query) => $query->whereNull('state')->orWhere('state', JobState::Hidden));

        return DB::transaction(function () use ($old) {
            AlsoOnSource::query()->whereIn('job_id', (clone $old)->select('id'))->delete();

            return $old->delete();
        });
    }

    /**
     * Sets the state of a job. The same state again clears it: the job moves back to New.
     * Any change marks the job as seen. Hiding stores tombstones, and leaving hidden deletes them.
     */
    public function setState(Job $job, ?JobState $state): Job
    {
        $next = $state === $job->state ? null : $state;
        $now = CarbonImmutable::now();

        DB::transaction(function () use ($job, $next, $now) {
            if ($job->state === JobState::Hidden && $next !== JobState::Hidden) {
                HiddenJob::query()->where('job_id', $job->id)->delete();
            }

            if ($next === JobState::Hidden) {
                $this->bury($job, $now);
            }

            $job->update([
                'state' => $next,
                'state_at' => $next === null ? null : $now,
                'seen_at' => $job->seen_at ?? $now,
            ]);
        });

        return $job;
    }

    /**
     * Stores one tombstone for each source key of the job, with its fingerprint.
     */
    private function bury(Job $job, CarbonImmutable $now): void
    {
        $keys = [[$job->source, $job->source_job_id]];

        foreach ($job->alsoOn()->orderBy('id')->get() as $other) {
            $keys[] = [$other->source, $other->source_job_id];
        }

        HiddenJob::query()->insert(array_map(fn (array $key) => [
            'job_id' => $job->id,
            'source' => $key[0],
            'source_job_id' => $key[1],
            'fingerprint' => $job->fingerprint,
            'created_at' => $now,
            'updated_at' => $now,
        ], $keys));
    }

    /**
     * A source key of a hidden job blocks forever. Its fingerprint blocks only for
     * DUPLICATE_DAYS after the job was hidden, the same window as deduplication.
     */
    private function isHidden(NormalisedJob $job): bool
    {
        $since = CarbonImmutable::now()->subDays(self::DUPLICATE_DAYS);

        return HiddenJob::query()
            ->where(fn ($query) => $query
                ->where(fn ($key) => $key->where('source', $job->source)->where('source_job_id', $job->sourceJobId))
                ->orWhere(fn ($fingerprint) => $fingerprint->where('fingerprint', $job->fingerprint)->where('created_at', '>=', $since)))
            ->exists();
    }

    private function saveOne(NormalisedJob $job): void
    {
        if ($this->isHidden($job)) {
            return;
        }

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

        $first ??= $this->firstCopy($job);

        if ($first !== null) {
            AlsoOnSource::query()->create(['job_id' => $first->id, 'source' => $job->source, 'source_job_id' => $job->sourceJobId] + $this->links($job));
            $this->seen($first->id, $job->fetchedAt);

            return;
        }

        Job::query()->create(Job::attributesOf($job));
    }

    /**
     * The first job from the same source that the new job copies: the same fingerprint,
     * first stored in the last DUPLICATE_DAYS, the same eligibility and the same salary.
     */
    private function firstCopy(NormalisedJob $job): ?Job
    {
        return Job::query()
            ->where('source', $job->source)
            ->where('fingerprint', $job->fingerprint)
            ->where('created_at', '>=', CarbonImmutable::now()->subDays(self::DUPLICATE_DAYS))
            ->orderBy('id')
            ->get()
            ->first(fn (Job $stored) => $this->identical($stored, $job));
    }

    /**
     * Eligibility (type, regions, countries as sets) and salary (min, max, currency, period) are the same.
     */
    private function identical(Job $stored, NormalisedJob $job): bool
    {
        $sorted = function (array $values): array {
            sort($values);

            return $values;
        };

        return $stored->eligibility_type === $job->eligibility->type->value
            && $sorted($stored->eligibility_regions) === $sorted($job->eligibility->regions)
            && $sorted($stored->eligibility_countries) === $sorted($job->eligibility->countries)
            && $stored->salary_min === $job->salary?->min
            && $stored->salary_max === $job->salary?->max
            && $stored->salary_currency === $job->salary?->currency
            && $stored->salary_period === $job->salary?->period;
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
     * The job was seen through another source or a copy: retention counts from this fetch.
     */
    private function seen(int $jobId, CarbonImmutable $fetchedAt): void
    {
        Job::query()->whereKey($jobId)->where('fetched_at', '<', $fetchedAt)->update(['fetched_at' => $fetchedAt]);
    }
}
