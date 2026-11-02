<?php

use App\Parsers\Title\TitleParser;
use App\Schema\ContractType;
use App\Schema\Seniority;
use Tests\Support\FixtureTitles;

it('reads the real titles of the fixtures', function (string $file, string $title, string $seniority, string $contractType) {
    $titles = array_map(fn (array $row) => $row[1], array_filter(FixtureTitles::all(), fn (array $row) => $row[0] === $file));

    expect($titles)->toContain($title);

    $result = (new TitleParser)->parse($title);

    expect($result->seniority)->toBe(Seniority::from($seniority))
        ->and($result->contractType)->toBe(ContractType::from($contractType));
})->with(require __DIR__.'/title-table.php');

it('has a row for every title in the fixtures', function () {
    $table = array_map(fn (array $row) => [$row[0], $row[1]], array_values(require __DIR__.'/title-table.php'));

    foreach (FixtureTitles::all() as $row) {
        expect($table)->toContain($row);
    }

    // A new fixture file needs its title field in FixtureTitles::fields().
    $files = array_map(
        fn (string $path) => substr($path, strlen(dirname(__DIR__, 3).'/Fixtures/')),
        glob(dirname(__DIR__, 3).'/Fixtures/*/*') ?: [],
    );
    $known = [...array_keys(FixtureTitles::fields()), 'ecb/eurofxref-daily.xml'];

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
