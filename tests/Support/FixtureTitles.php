<?php

namespace Tests\Support;

use Closure;
use RuntimeException;

/**
 * The job titles of the fixture files, as each source reads them.
 */
final class FixtureTitles
{
    /**
     * The title field of each fixture file.
     *
     * @return array<string, Closure(string): array<mixed>>
     */
    public static function fields(): array
    {
        $dir = self::dir();
        $json = fn (string $file) => json_decode((string) file_get_contents($dir.$file), true);
        $xml = fn (string $file) => simplexml_load_file($dir.$file) ?: throw new RuntimeException("{$file} is not XML");

        return [
            'greenhouse/cloudflare-levels.json' => fn ($file) => array_column($json($file)['jobs'], 'title'),
            'greenhouse/gitlab-levels.json' => fn ($file) => array_column($json($file)['jobs'], 'title'),
            'greenhouse/gitlab-remote.json' => fn ($file) => array_column($json($file)['jobs'], 'title'),
            'greenhouse/jobs.json' => fn ($file) => array_column($json($file)['jobs'], 'title'),
            'greenhouse/twilio-levels.json' => fn ($file) => array_column($json($file)['jobs'], 'title'),
            'lever/postings.json' => fn ($file) => array_column($json($file), 'text'),
            'lever/postings-salary.json' => fn ($file) => array_column($json($file), 'text'),
            'ashby/job-board.json' => fn ($file) => array_column($json($file)['jobs'], 'title'),
            'personio/positions.xml' => fn ($file) => array_map('strval', $xml($file)->xpath('/workzag-jobs/position/name') ?: []),
            'personio/positions-empty.xml' => fn ($file) => array_map('strval', $xml($file)->xpath('/workzag-jobs/position/name') ?: []),
            'larajobs/feed.xml' => fn ($file) => array_map('strval', $xml($file)->xpath('//item/title') ?: []),
            'jobicy/remote-jobs.json' => fn ($file) => array_column($json($file)['jobs'], 'jobTitle'),
            'jobicy/remote-jobs-junior.json' => fn ($file) => array_column($json($file)['jobs'], 'jobTitle'),
            'jobicy/jobs.json' => fn ($file) => array_column($json($file)['jobs'], 'jobTitle'),
            'remotive/remote-jobs.json' => fn ($file) => array_column($json($file)['jobs'], 'title'),
            'remotive/remote-jobs-contract.json' => fn ($file) => array_column($json($file)['jobs'], 'title'),
            'himalayas/jobs.json' => fn ($file) => array_column($json($file)['jobs'], 'title'),
            'himalayas/search.json' => fn ($file) => array_column($json($file)['jobs'], 'title'),
            'himalayas/search-mid.json' => fn ($file) => array_column($json($file)['jobs'], 'title'),
            'weworkremotely/back-end.rss' => fn ($file) => array_map(
                fn ($title) => explode(': ', (string) $title, 2)[1],
                $xml($file)->xpath('//item/title') ?: [],
            ),
        ];
    }

    /**
     * Every job title in the fixtures.
     *
     * @return list<array{0: string, 1: string}> [file, title]
     */
    public static function all(): array
    {
        $titles = [];

        foreach (self::fields() as $file => $read) {
            foreach ($read($file) as $title) {
                $titles[] = [$file, (string) $title];
            }
        }

        return $titles;
    }

    public static function dir(): string
    {
        return dirname(__DIR__).'/Fixtures/';
    }
}
