<?php

namespace App\Sources;

use App\Models\SourceRun;
use App\Parsers\Location\UnknownEligibilityShare;
use App\Parsers\Salary\UnknownSalaryShare;
use App\Settings\Settings;
use App\Storage\JobStore;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runs the enabled sources that are due, stores their jobs and one source_runs
 * row per run. A failing source does not stop the others. No scheduler here:
 * M6c calls it from hire:scan, M7 from NativePHP.
 */
final class SourceRunner
{
    public function __construct(
        private readonly SourceRegistry $registry,
        private readonly JobStore $store,
        private readonly NormaliseItems $normalise,
    ) {}

    /**
     * @return list<SourceRun> the runs started now, in registry order
     */
    public function run(Settings $settings): array
    {
        $runs = [];

        foreach ($this->registry->enabled($settings) as $source) {
            if ($this->isDue($source)) {
                $runs[] = $this->runOne($source);
            }
        }

        return $runs;
    }

    /**
     * Due when the last run started at least minInterval() ago, also after a failed run.
     */
    public function isDue(JobSource $source): bool
    {
        $last = SourceRun::query()->where('source', $source->key())->latest('started_at')->first();

        return $last === null || $last->started_at->lte(CarbonImmutable::now()->sub($source->minInterval()));
    }

    private function runOne(JobSource $source): SourceRun
    {
        // $since is the start of the last successful run, so a failed run loses no jobs.
        $since = SourceRun::query()->where('source', $source->key())->succeeded()->latest('started_at')->first()?->started_at;
        $run = SourceRun::query()->create(['source' => $source->key(), 'started_at' => CarbonImmutable::now()]);

        try {
            $batch = ($this->normalise)($source, $since);
            $this->store->save($batch->jobs);

            $run->update([
                'finished_at' => CarbonImmutable::now(),
                'items_fetched' => count($batch->jobs) + $batch->skipped,
                'items_skipped' => $batch->skipped,
                'unknown_eligibility_share' => UnknownEligibilityShare::of($batch->jobs)->share,
                'unknown_salary_share' => UnknownSalaryShare::of($batch->jobs)->share,
            ]);
        } catch (Throwable $error) {
            Log::warning("Source {$source->key()} failed: {$error->getMessage()}");

            $run->update([
                'finished_at' => CarbonImmutable::now(),
                'error' => $error->getMessage(),
                'error_kind' => RunErrorKind::of($error)->value,
            ]);
        }

        return $run;
    }
}
