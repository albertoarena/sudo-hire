<?php

namespace App\Scan;

use App\List\ListRow;
use App\Models\SourceRun;
use App\Settings\SettingsStore;
use App\Sources\JobSource;
use App\Sources\SourceRegistry;
use App\Sources\SourceRunner;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * The progress of the first scan, for the last screen of the first run. Each enabled source is
 * waiting, reading, done or failed, from its source_runs rows. A source that is not due was
 * read by an earlier scan, so it counts as done (or failed) already.
 */
final class FirstScanProgress
{
    private const string STARTED_AT = 'first-scan.started-at';

    public function __construct(
        private readonly SourceRegistry $registry,
        private readonly SourceRunner $runner,
        private readonly SettingsStore $settings,
    ) {}

    public function start(): void
    {
        Cache::forever(self::STARTED_AT, CarbonImmutable::now()->toIso8601ZuluString());
    }

    /**
     * @return array{sources: list<array{key: string, name: string, status: string, errorKind: string|null}>, finished: bool}
     */
    public function toArray(): array
    {
        $started = Cache::get(self::STARTED_AT);
        $startedAt = is_string($started) ? CarbonImmutable::parse($started) : null;
        $sources = [];

        foreach ($this->registry->enabled($this->settings->load()) as $source) {
            $last = SourceRun::query()->where('source', $source->key())->latest('started_at')->latest('id')->first();
            $status = $last === null ? 'waiting' : $this->status($source, $last, $startedAt);
            $sources[] = [
                'key' => $source->key(),
                'name' => ucfirst(ListRow::name($source->attribution())),
                'status' => $status,
                'errorKind' => $status === 'failed' ? $last?->error_kind : null,
            ];
        }

        return [
            'sources' => $sources,
            'finished' => array_filter($sources, fn (array $source) => in_array($source['status'], ['waiting', 'reading'], true)) === [],
        ];
    }

    /**
     * Reading while the last run has not finished. Done or failed when the last run started after
     * the first scan, or when the source is not due. Else the scan has not reached the source yet.
     */
    private function status(JobSource $source, SourceRun $last, ?CarbonImmutable $startedAt): string
    {
        if ($last->finished_at === null) {
            return 'reading';
        }

        if ($startedAt === null || $last->started_at->gte($startedAt) || ! $this->runner->isDue($source)) {
            return $last->error_kind === null ? 'done' : 'failed';
        }

        return 'waiting';
    }
}
