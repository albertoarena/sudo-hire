<?php

namespace App\Filters;

use App\Schema\NormalisedJob;
use App\Settings\Settings;

/**
 * Include and exclude keywords. They match the title and the tags, and the
 * description when the setting is on. Case-insensitive, whole words. Pure: no I/O.
 */
final class KeywordFilter
{
    /** "#" and "+" are part of a word, so "c" does not match "c#" or "c++". */
    private const string WORD = '[\p{L}\p{N}_#+]';

    /**
     * An empty include list lets every job pass.
     */
    public static function passesInclude(NormalisedJob $job, Settings $settings): bool
    {
        return $settings->keywordsInclude === [] || self::matches($job, $settings, $settings->keywordsInclude);
    }

    public static function passesExclude(NormalisedJob $job, Settings $settings): bool
    {
        return ! self::matches($job, $settings, $settings->keywordsExclude);
    }

    /**
     * @param  list<string>  $keywords
     */
    private static function matches(NormalisedJob $job, Settings $settings, array $keywords): bool
    {
        $texts = [$job->title, ...$job->tags];

        if ($settings->matchDescription && $job->description !== null) {
            $texts[] = $job->description;
        }

        foreach ($keywords as $keyword) {
            $pattern = '/(?<!'.self::WORD.')'.preg_quote($keyword, '/').'(?!'.self::WORD.')/iu';

            foreach ($texts as $text) {
                if (preg_match($pattern, $text) === 1) {
                    return true;
                }
            }
        }

        return false;
    }
}
