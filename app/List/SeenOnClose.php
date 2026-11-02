<?php

namespace App\List;

use App\Models\Job;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * Seen on close: the New matches that existed when the list opened become seen when it
 * closes. A job stored while the list was open stays unseen.
 */
final class SeenOnClose
{
    public const string CACHE_KEY = 'sudo-hire.list-opened-at';

    public function __construct(private readonly JobList $list) {}

    public function opened(): void
    {
        Cache::forever(self::CACHE_KEY, CarbonImmutable::now()->toIso8601ZuluString());
    }

    /**
     * @return int the number of jobs marked as seen
     */
    public function closed(): int
    {
        $openedAt = Cache::pull(self::CACHE_KEY);

        if (! is_string($openedAt)) {
            return 0;
        }

        $ids = $this->list->unseenIds(CarbonImmutable::parse($openedAt));

        return $ids === [] ? 0 : Job::query()->whereKey($ids)->whereNull('seen_at')->update(['seen_at' => CarbonImmutable::now()]);
    }
}
