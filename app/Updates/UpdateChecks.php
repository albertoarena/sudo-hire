<?php

namespace App\Updates;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Repository;

/**
 * Remembers the last update check, so the app checks GitHub at most once a day.
 * The app checks when it starts (NativePHP does this), and hire:check-updates checks
 * again when the last check is one day old.
 */
final class UpdateChecks
{
    public const string KEY = 'updates.last_check';

    public const int INTERVAL_HOURS = 24;

    public function __construct(private readonly Repository $cache) {}

    public function record(?CarbonImmutable $at = null): void
    {
        $this->cache->forever(self::KEY, ($at ?? CarbonImmutable::now())->getTimestamp());
    }

    public function lastCheck(): ?CarbonImmutable
    {
        $timestamp = $this->cache->get(self::KEY);

        return is_int($timestamp) ? CarbonImmutable::createFromTimestamp($timestamp) : null;
    }

    public function isDue(): bool
    {
        $last = $this->lastCheck();

        return $last === null || $last->addHours(self::INTERVAL_HOURS)->lte(CarbonImmutable::now());
    }
}
