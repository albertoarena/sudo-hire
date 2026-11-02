<?php

namespace App\Scan;

use App\Currency\CurrencyConverter;
use App\Currency\ExchangeRates;
use App\Filters\JobFilter;
use App\Models\AlsoOnSource;
use App\Models\Job;
use App\Models\SentNotification;
use App\Settings\Settings;
use App\Settings\SettingsStore;
use App\Sources\SourceRunner;
use App\Storage\JobStore;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LogicException;
use Throwable;

/**
 * One scan: refresh the rates, run the due sources, prune, filter the new jobs,
 * and send the summary notification when it is due. hire:scan runs it, from the
 * terminal, from cron or launchd, and from the NativePHP scheduler.
 */
final class Scan
{
    public function __construct(
        private readonly ExchangeRates $rates,
        private readonly SettingsStore $settings,
        private readonly SourceRunner $runner,
        private readonly JobStore $store,
        private readonly Notifier $notifier,
    ) {}

    /**
     * @param  bool  $notify  false: find the matches, but send nothing and set no notified_at
     */
    public function run(bool $notify = true): ScanResult
    {
        $this->refreshRates();

        $settings = $this->settings->load();
        $runs = $this->runner->run($settings);
        $this->store->prune();

        $now = CarbonImmutable::now();
        $converter = $this->rates->converter();
        $matches = [];
        $locationNotStated = 0;

        foreach ($this->store->notNotified()->load('alsoOn') as $job) {
            $match = $this->match($job, $settings, $converter, $now);

            if ($match === null) {
                continue;
            }

            if ($match->result->locationNotStated) {
                $locationNotStated++;
            } else {
                $matches[] = $match;
            }
        }

        $lastSentAt = SentNotification::query()->latest('sent_at')->first()?->sent_at;

        if (! $notify) {
            return new ScanResult($runs, $matches, $locationNotStated, NotificationOutcome::Off, lastSentAt: $lastSentAt);
        }

        // Without a country the eligibility filter is off, so the matches cannot be trusted.
        if ($settings->country === null) {
            return new ScanResult($runs, $matches, $locationNotStated, NotificationOutcome::NoCountry, lastSentAt: $lastSentAt);
        }

        if ($matches === []) {
            return new ScanResult($runs, $matches, $locationNotStated, NotificationOutcome::NoMatches, lastSentAt: $lastSentAt);
        }

        if ($lastSentAt !== null && $lastSentAt->gt($now->subHours($settings->notificationIntervalHours))) {
            return new ScanResult($runs, $matches, $locationNotStated, NotificationOutcome::NotDue, lastSentAt: $lastSentAt);
        }

        $text = SummaryText::of(array_map(fn (ScanMatch $match) => $match->job->toNormalised(), $matches), $settings);

        try {
            $this->notifier->send($text);
        } catch (NotificationFailed $failed) {
            Log::warning("Notification failed: {$failed->getMessage()}");

            return new ScanResult($runs, $matches, $locationNotStated, NotificationOutcome::Failed, lastSentAt: $lastSentAt, notificationError: $failed->getMessage());
        }

        $sent = DB::transaction(function () use ($matches, $text, $now) {
            Job::query()->whereKey(array_map(fn (ScanMatch $match) => $match->job->id, $matches))->update(['notified_at' => $now]);

            return SentNotification::query()->create(['sent_at' => $now, 'job_count' => count($matches), 'text' => $text]);
        });

        return new ScanResult($runs, $matches, $locationNotStated, NotificationOutcome::Sent, $sent, $lastSentAt);
    }

    /**
     * A failed fetch keeps the last rates and does not stop the scan.
     */
    private function refreshRates(): void
    {
        try {
            $this->rates->refresh();
        } catch (Throwable $error) {
            Log::warning("Exchange rates refresh failed: {$error->getMessage()}");
        }
    }

    private function match(Job $job, Settings $settings, CurrencyConverter $converter, CarbonImmutable $now): ?ScanMatch
    {
        $alsoOn = array_values($job->alsoOn->sortBy('id')->all());
        $normalised = $job->toNormalised();
        $result = JobFilter::evaluate($normalised, $settings, $converter, $now, array_map(fn (AlsoOnSource $other) => $other->source, $alsoOn));

        if (! $result->passes) {
            return null;
        }

        $salary = SalaryText::of($normalised->salary, $result, $converter, $settings->displayCurrency);

        // The links of the first enabled source: the main source, else the first "also on" source.
        if (! in_array($job->source, $settings->disabledSources, true)) {
            return new ScanMatch($job, $result, $job->source, $job->source_url, $job->apply_url, $job->attribution, $salary);
        }

        foreach ($alsoOn as $other) {
            if (! in_array($other->source, $settings->disabledSources, true)) {
                return new ScanMatch($job, $result, $other->source, $other->source_url, $other->apply_url, $other->attribution, $salary);
            }
        }

        throw new LogicException("Job {$job->id} passed the source filter with no enabled source.");
    }
}
