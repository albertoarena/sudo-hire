<?php

namespace App\List;

use App\Currency\ExchangeRates;
use App\Models\Job;
use App\Scan\JobMatcher;
use App\Scan\ScanMatch;
use App\Settings\SettingsStore;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

/**
 * Builds the menu bar list from all stored jobs and the stored settings.
 * New holds the matches with no state. Saved and Applied hold the jobs in that
 * state, also when they fail the filters now. Hidden jobs are not in the list.
 */
final class JobList
{
    public function __construct(
        private readonly SettingsStore $settings,
        private readonly ExchangeRates $rates,
    ) {}

    public function build(): ListView
    {
        $settings = $this->settings->load();
        $converter = $this->rates->converter();
        $now = CarbonImmutable::now();
        $tabs = ['new' => [], 'group' => [], 'saved' => [], 'applied' => []];

        foreach ($this->visibleJobs() as $job) {
            $match = JobMatcher::evaluate($job, $settings, $converter, $now);
            $tab = $this->tab($match);

            if ($tab !== null) {
                $tabs[$tab][] = $match;
            }
        }

        $newestPost = fn (ScanMatch $a, ScanMatch $b) => [$b->job->posted_at, $b->job->id] <=> [$a->job->posted_at, $a->job->id];
        $newestState = fn (ScanMatch $a, ScanMatch $b) => [$b->job->state_at, $b->job->id] <=> [$a->job->state_at, $a->job->id];
        $rows = function (array $matches, callable $order) use ($settings, $converter): array {
            usort($matches, $order);

            return array_map(fn (ScanMatch $match) => ListRow::of($match, $settings, $converter), $matches);
        };

        return new ListView(
            new: $rows($tabs['new'], $newestPost),
            locationNotStated: $rows($tabs['group'], $newestPost),
            saved: $rows($tabs['saved'], $newestState),
            applied: $rows($tabs['applied'], $newestState),
        );
    }

    /**
     * The unseen New matches, without the "Location not stated" group.
     */
    public function unseenCount(): int
    {
        return $this->build()->unseen();
    }

    /**
     * The ids of the unseen New matches, without the group, that were stored at or before $existedAt.
     *
     * @return list<int>
     */
    public function unseenIds(CarbonImmutable $existedAt): array
    {
        $ids = [];

        foreach ($this->build()->new as $row) {
            if ($row['seen'] === false) {
                $ids[] = $row['id'];
            }
        }

        if ($ids === []) {
            return [];
        }

        /** @var list<int> */
        return Job::query()->whereKey($ids)->where('created_at', '<=', $existedAt)->orderBy('id')->pluck('id')->all();
    }

    /**
     * @return Collection<int, Job>
     */
    private function visibleJobs(): Collection
    {
        return Job::query()
            ->where(fn ($query) => $query->whereNull('state')->orWhere('state', '!=', JobState::Hidden))
            ->with('alsoOn')
            ->get();
    }

    private function tab(ScanMatch $match): ?string
    {
        return match ($match->job->state) {
            JobState::Saved => 'saved',
            JobState::Applied => 'applied',
            null => $match->result->passes ? ($match->result->locationNotStated ? 'group' : 'new') : null,
            default => null,
        };
    }
}
