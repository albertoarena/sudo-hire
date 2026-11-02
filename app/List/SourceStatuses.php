<?php

namespace App\List;

use App\Models\AlsoOnSource;
use App\Models\Job;
use App\Models\SourceRun;
use App\Settings\SettingsStore;
use App\Sources\SourceRegistry;

/**
 * The status of each source for the Sources pane and the diagnostic report.
 */
final class SourceStatuses
{
    public function __construct(
        private readonly SourceRegistry $registry,
        private readonly SettingsStore $settings,
    ) {}

    /**
     * @return list<array{key: string, name: string, enabled: bool, minIntervalMinutes: int, lastRunAt: string|null, lastSuccessAt: string|null, jobCount: int, lastError: array{message: string, kind: string}|null}>
     */
    public function all(): array
    {
        $disabled = $this->settings->load()->disabledSources;
        $statuses = [];

        foreach ($this->registry->keys() as $key) {
            $source = $this->registry->source($key);
            $last = SourceRun::query()->where('source', $key)->latest('started_at')->latest('id')->first();
            $success = SourceRun::query()->where('source', $key)->succeeded()->latest('started_at')->latest('id')->first();

            $statuses[] = [
                'key' => $key,
                'name' => ucfirst(ListRow::name($source->attribution())),
                'enabled' => ! in_array($key, $disabled, true),
                'minIntervalMinutes' => (int) $source->minInterval()->totalMinutes,
                'lastRunAt' => $last?->started_at->utc()->toIso8601ZuluString(),
                'lastSuccessAt' => $success?->finished_at?->utc()->toIso8601ZuluString(),
                'jobCount' => Job::query()->where('source', $key)->count() + AlsoOnSource::query()->where('source', $key)->count(),
                'lastError' => $last?->error_kind === null ? null : ['message' => (string) $last->error, 'kind' => $last->error_kind],
            ];
        }

        return $statuses;
    }
}
