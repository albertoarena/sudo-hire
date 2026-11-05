<?php

namespace App\Scan;

use App\Filters\KeywordFilter;
use App\Schema\NormalisedJob;
use App\Settings\Settings;

/**
 * The text of the summary notification, e.g. "6 new jobs: 4 Laravel, 2 PHP"
 * (REQUIREMENTS section 10). Pure: no I/O.
 */
final class SummaryText
{
    /**
     * Each job counts once, under the first include keyword that it matches,
     * in the order of the settings. With no include keywords, only the count.
     * Each keyword starts with a capital letter; the rest stays as the user wrote it.
     *
     * @param  list<NormalisedJob>  $jobs
     */
    public static function of(array $jobs, Settings $settings): string
    {
        $count = count($jobs);
        $text = $count === 1 ? '1 new job' : "{$count} new jobs";

        $groups = array_fill_keys($settings->keywordsInclude, 0);

        foreach ($jobs as $job) {
            $keyword = KeywordFilter::firstInclude($job, $settings);

            if ($keyword !== null) {
                $groups[$keyword]++;
            }
        }

        $parts = [];

        foreach (array_filter($groups) as $keyword => $jobCount) {
            $parts[] = $jobCount.' '.self::capitalised((string) $keyword);
        }

        return $parts === [] ? $text : $text.': '.implode(', ', $parts);
    }

    private static function capitalised(string $keyword): string
    {
        return mb_strtoupper(mb_substr($keyword, 0, 1)).mb_substr($keyword, 1);
    }
}
