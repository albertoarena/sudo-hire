<?php

namespace App\Console\Commands;

use App\Models\SourceRun;
use App\Scan\NotificationOutcome;
use App\Scan\Scan;
use App\Scan\ScanMatch;
use App\Scan\ScanResult;
use App\Schema\Eligibility;
use App\Schema\EligibilityType;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;

/**
 * One scan from the terminal, cron or launchd. A second scan that starts while
 * one runs exits at once, also without the --isolated option.
 */
#[Signature('hire:scan {--no-notify : Print the matches only. Send no notification and set no notified_at.}')]
#[Description('Fetch the due sources, filter the new jobs, print the matches and send the summary notification')]
class HireScanCommand extends Command implements Isolatable
{
    /** Isolatable is active only with --isolated. This makes every run isolated. */
    protected $isolated = true;

    public function handle(Scan $scan): int
    {
        $result = $scan->run(notify: ! $this->option('no-notify'));

        $this->printMatches($result);
        $this->printRuns($result);
        $this->printNotification($result);

        // A failing source or notifier is in the output, not in the exit code.
        return self::SUCCESS;
    }

    private function printMatches(ScanResult $result): void
    {
        if ($result->matches === []) {
            $this->info('No new matches.');
        } else {
            $this->table(
                ['Title', 'Company', 'Salary', 'Eligibility', 'Source', 'Posted', 'Apply'],
                array_map(fn (ScanMatch $match) => [
                    $match->job->title,
                    $match->job->company,
                    $match->salary,
                    $this->eligibility($match->job->toNormalised()->eligibility),
                    $match->attribution,
                    $match->job->posted_at->utc()->format('Y-m-d'),
                    $match->applyUrl,
                ], $result->matches),
            );
        }

        if ($result->locationNotStated > 0) {
            $jobs = $result->locationNotStated === 1 ? '1 job' : "{$result->locationNotStated} jobs";
            $this->line("Location not stated: {$jobs}, not listed.");
        }
    }

    private function printRuns(ScanResult $result): void
    {
        if ($result->runs === []) {
            $this->line('No source was due.');

            return;
        }

        $this->line('Sources:');

        foreach ($result->runs as $run) {
            $this->line('  '.$this->runLine($run));
        }
    }

    private function runLine(SourceRun $run): string
    {
        // A failed run keeps the database default of 0, which the model in memory does not have.
        $count = (int) $run->getAttribute('items_fetched');
        $items = $count === 1 ? '1 item' : "{$count} items";

        return $run->error_kind === null
            ? "{$run->source}: {$items}"
            : "{$run->source}: {$items}, error: {$run->error_kind}";
    }

    private function printNotification(ScanResult $result): void
    {
        match ($result->notification) {
            NotificationOutcome::Sent => $this->info("Notification sent: {$result->sent?->text}"),
            NotificationOutcome::NoMatches => $this->line('No notification: 0 new matches.'),
            NotificationOutcome::NotDue => $this->line('Notification not due: the last one was sent at '.$result->lastSentAt?->utc()->format('Y-m-d H:i').' UTC.'),
            NotificationOutcome::Off => $this->line('Notification off (--no-notify).'),
            NotificationOutcome::Failed => $this->error("Notification failed: {$result->notificationError}"),
            NotificationOutcome::NoCountry => $this->line('No notification: set your country first.'),
            NotificationOutcome::FirstScan => $this->line('No notification: first scan. '.(count($result->matches) === 1 ? 'Its 1 job counts' : 'Its '.count($result->matches).' jobs count').' as notified.'),
        };
    }

    private function eligibility(Eligibility $eligibility): string
    {
        return match ($eligibility->type) {
            EligibilityType::Worldwide => 'Worldwide',
            EligibilityType::Unknown => 'Location not stated',
            EligibilityType::Restricted => implode(', ', [...$eligibility->regions, ...$eligibility->countries]),
        };
    }
}
