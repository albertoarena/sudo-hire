<?php

namespace App\List;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * The start and end of the last scan, for the status bar. The app scans every 5 minutes.
 */
final class LastScan
{
    public const string CACHE_KEY = 'sudo-hire.last-scan';

    public const int INTERVAL_MINUTES = 5;

    public function record(CarbonImmutable $startedAt, CarbonImmutable $finishedAt): void
    {
        Cache::forever(self::CACHE_KEY, [
            'startedAt' => $startedAt->utc()->toIso8601ZuluString(),
            'finishedAt' => $finishedAt->utc()->toIso8601ZuluString(),
        ]);
    }

    /**
     * @return array{startedAt: string|null, finishedAt: string|null, nextAt: string|null}
     */
    public function toArray(): array
    {
        $last = Cache::get(self::CACHE_KEY);
        $startedAt = is_array($last) && is_string($last['startedAt'] ?? null) ? $last['startedAt'] : null;
        $finishedAt = is_array($last) && is_string($last['finishedAt'] ?? null) ? $last['finishedAt'] : null;

        return [
            'startedAt' => $startedAt,
            'finishedAt' => $finishedAt,
            'nextAt' => $startedAt === null ? null : CarbonImmutable::parse($startedAt)->addMinutes(self::INTERVAL_MINUTES)->toIso8601ZuluString(),
        ];
    }
}
