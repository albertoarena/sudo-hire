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
     * The first include keyword, in the order of the settings, that the job matches.
     * Null when no include keyword matches or the list is empty. The summary
     * notification groups the jobs by it.
     */
    public static function firstInclude(NormalisedJob $job, Settings $settings): ?string
    {
        return self::firstMatch($job, $settings, $settings->keywordsInclude)['keyword'] ?? null;
    }

    /**
     * The first include keyword, as firstInclude gives it, and the field it matched:
     * "title", "tags" or "description", in that order. The list row shows it, so a
     * job that matched only a tag the board added does not look like a wrong match.
     *
     * @return array{keyword: string, field: string}|null
     */
    public static function includeMatch(NormalisedJob $job, Settings $settings): ?array
    {
        return self::firstMatch($job, $settings, $settings->keywordsInclude);
    }

    /**
     * @param  list<string>  $keywords
     */
    private static function matches(NormalisedJob $job, Settings $settings, array $keywords): bool
    {
        return self::firstMatch($job, $settings, $keywords) !== null;
    }

    /**
     * @param  list<string>  $keywords
     * @return array{keyword: string, field: string}|null
     */
    private static function firstMatch(NormalisedJob $job, Settings $settings, array $keywords): ?array
    {
        $fields = [['title', $job->title], ...array_map(fn (string $tag) => ['tags', $tag], $job->tags)];

        if ($settings->matchDescription && $job->description !== null) {
            $fields[] = ['description', $job->description];
        }

        foreach ($keywords as $keyword) {
            $pattern = '/(?<!'.self::WORD.')'.preg_quote($keyword, '/').'(?!'.self::WORD.')/iu';

            foreach ($fields as [$field, $text]) {
                if (preg_match($pattern, $text) === 1) {
                    return ['keyword' => $keyword, 'field' => $field];
                }
            }
        }

        return null;
    }
}
