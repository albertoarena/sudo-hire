<?php

use App\Parsers\Title\TitleParser;
use App\Schema\ContractType;
use App\Schema\Seniority;

/**
 * The title field of each fixture file.
 *
 * @return array<string, Closure(string): array<mixed>>
 */
function titleFields(): array
{
    $dir = dirname(__DIR__, 3).'/Fixtures/';
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
function fixtureTitles(): array
{
    $titles = [];

    foreach (titleFields() as $file => $read) {
        foreach ($read($file) as $title) {
            $titles[] = [$file, (string) $title];
        }
    }

    return $titles;
}

it('reads the real titles of the fixtures', function (string $file, string $title, string $seniority, string $contractType) {
    $titles = array_map(fn (array $row) => $row[1], array_filter(fixtureTitles(), fn (array $row) => $row[0] === $file));

    expect($titles)->toContain($title);

    $result = (new TitleParser)->parse($title);

    expect($result->seniority)->toBe(Seniority::from($seniority))
        ->and($result->contractType)->toBe(ContractType::from($contractType));
})->with(require __DIR__.'/title-table.php');

it('has a row for every title in the fixtures', function () {
    $table = array_map(fn (array $row) => [$row[0], $row[1]], array_values(require __DIR__.'/title-table.php'));

    foreach (fixtureTitles() as $row) {
        expect($table)->toContain($row);
    }

    // A new fixture file needs its title field in fixtureTitles().
    $files = array_map(
        fn (string $path) => substr($path, strlen(dirname(__DIR__, 3).'/Fixtures/')),
        glob(dirname(__DIR__, 3).'/Fixtures/*/*') ?: [],
    );
    $known = [...array_keys(titleFields()), 'ecb/eurofxref-daily.xml'];

    expect(array_values(array_diff($files, $known)))->toBe([]);
});

// Not from a fixture: no real title has these words. Approved exception,
// see "Synthetic titles for the title parser" in docs/DECISIONS.md.
dataset('syntheticTitles', [
    'Jr' => ['Jr Software Engineer', 'junior', 'unknown'],
    'Entry level' => ['Entry level Support Engineer', 'junior', 'unknown'],
    'Graduate' => ['Graduate Software Engineer', 'junior', 'unknown'],
    'Part-time' => ['Part-time Support Engineer', 'unknown', 'part_time'],
    'Part time' => ['Support Engineer (Part time)', 'unknown', 'part_time'],
    'Parttime' => ['Support Engineer Parttime', 'unknown', 'part_time'],
    'Full-time' => ['Full-time Support Engineer', 'unknown', 'full_time'],
    'Full time' => ['Support Engineer (Full time)', 'unknown', 'full_time'],
    'Fulltime' => ['Support Engineer Fulltime', 'unknown', 'full_time'],
    'Leading is not Lead' => ['Engineer at a Leading Bank', 'unknown', 'unknown'],
    'Seniority is not Senior' => ['Seniority Data Analyst', 'unknown', 'unknown'],
]);

it('reads list words that no real title has (not from a fixture)', function (string $title, string $seniority, string $contractType) {
    $result = (new TitleParser)->parse($title);

    expect($result->seniority)->toBe(Seniority::from($seniority))
        ->and($result->contractType)->toBe(ContractType::from($contractType));
})->with('syntheticTitles');
