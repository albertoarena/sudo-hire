<?php

namespace App\List;

use App\Currency\ExchangeRates;
use App\Models\Job;
use App\Scan\JobMatcher;
use App\Settings\Settings;
use App\Settings\SettingsStore;
use Carbon\CarbonImmutable;

/**
 * The stamps of two stored jobs for a country that is not saved yet, for the
 * settings and the first run. It prefers one job that the country admits and one
 * that it refuses, newest post first. A job with no stated location comes last.
 */
final class StampPreview
{
    private const int SIZE = 2;

    public function __construct(
        private readonly SettingsStore $settings,
        private readonly ExchangeRates $rates,
    ) {}

    /**
     * @return list<array{id: int, title: string, company: string, stamp: array{kind: string, place: string|null}}>
     */
    public function for(string $country): array
    {
        $settings = Settings::fromArray(array_merge($this->settings->load()->toArray(), ['country' => $country]));
        $converter = $this->rates->converter();
        $now = CarbonImmutable::now();
        $rows = [];

        $jobs = Job::query()
            ->where(fn ($query) => $query->whereNull('state')->orWhere('state', '!=', JobState::Hidden))
            ->with('alsoOn')
            ->orderByDesc('posted_at')
            ->orderByDesc('id')
            ->lazy();

        foreach ($jobs as $job) {
            $match = JobMatcher::evaluate($job, $settings, $converter, $now);
            $stamp = Stamp::of($job->toNormalised(), $settings, $match->result)->toArray();
            $rows[] = ['id' => $job->id, 'title' => $job->title, 'company' => $job->company, 'stamp' => $stamp];

            if (self::of($rows, Stamp::ADMITTED) !== [] && self::of($rows, Stamp::REFUSED) !== []) {
                break;
            }
        }

        $admitted = self::of($rows, Stamp::ADMITTED);
        $refused = self::of($rows, Stamp::REFUSED);
        $first = array_filter([$admitted[0] ?? null, $refused[0] ?? null]);
        $rest = [...array_slice($admitted, 1), ...array_slice($refused, 1), ...self::of($rows, Stamp::NOT_STATED)];

        return array_slice([...$first, ...$rest], 0, self::SIZE);
    }

    /**
     * @template T of array{stamp: array{kind: string, place: string|null}}
     *
     * @param  list<T>  $rows
     * @return list<T>
     */
    private static function of(array $rows, string $kind): array
    {
        return array_values(array_filter($rows, fn (array $row) => $row['stamp']['kind'] === $kind));
    }
}
