<?php

namespace App\List;

use App\Currency\CurrencyConverter;
use App\Models\Job;
use App\Scan\SalaryText;
use App\Scan\ScanMatch;
use App\Settings\Settings;

/**
 * One job row of the menu bar list, as the API gives it to the UI.
 */
final class ListRow
{
    /**
     * @return array<string, mixed>
     */
    public static function of(ScanMatch $match, Settings $settings, CurrencyConverter $converter): array
    {
        $job = $match->job;
        $normalised = $job->toNormalised();

        return [
            'id' => $job->id,
            'title' => $job->title,
            'company' => $job->company,
            'source' => $match->source,
            'attribution' => $match->attribution,
            'alsoOn' => self::alsoOn($job, $match->source),
            'postedAt' => $job->posted_at->utc()->toIso8601ZuluString(),
            'salary' => [
                'original' => $normalised->salary?->raw,
                'converted' => SalaryText::forList($normalised->salary, $converter, $settings->displayCurrency, $settings->monthlyPayments),
                'unknown' => $match->result->salaryUnknown,
                'ote' => $match->result->ote,
            ],
            'terms' => ['contractType' => $job->contract_type->value, 'seniority' => $job->seniority->value],
            'stamp' => Stamp::of($normalised, $settings, $match->result)->toArray(),
            'sourceUrl' => $match->sourceUrl,
            'applyUrl' => $match->applyUrl,
            'seen' => $job->seen_at !== null,
            'state' => $job->state?->value,
        ];
    }

    /**
     * The names of the other sources of the job, from their attribution: "via LaraJobs" gives "LaraJobs".
     *
     * @return list<string>
     */
    private static function alsoOn(Job $job, string $shown): array
    {
        $sources = [[$job->source, $job->attribution]];

        foreach ($job->alsoOn->sortBy('id') as $other) {
            $sources[] = [$other->source, $other->attribution];
        }

        $names = [];

        foreach ($sources as [$source, $attribution]) {
            if ($source !== $shown) {
                $names[] = self::name($attribution);
            }
        }

        return $names;
    }

    public static function name(string $attribution): string
    {
        return (string) preg_replace('/^via\s+/i', '', trim($attribution));
    }
}
