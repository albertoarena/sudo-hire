<?php

const FIXTURES_DIR = __DIR__.'/../Fixtures';

/**
 * @return list<string> fixture paths relative to the repository root
 */
function fixtureFiles(): array
{
    $root = dirname(__DIR__, 2);
    $files = [];

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(FIXTURES_DIR, FilesystemIterator::SKIP_DOTS));

    foreach ($iterator as $file) {
        $files[] = substr($file->getRealPath(), strlen($root) + 1);
    }

    sort($files);

    return $files;
}

it('has a fixture folder for every v1 source', function (string $source) {
    expect(glob(FIXTURES_DIR."/{$source}/*"))->not->toBeEmpty();
})->with([
    'greenhouse', 'lever', 'ashby', 'personio',
    'larajobs', 'jobicy', 'remotive', 'himalayas', 'weworkremotely',
]);

it('has a real ECB rates fixture with a date and rates', function () {
    $xml = simplexml_load_file(FIXTURES_DIR.'/ecb/eurofxref-daily.xml');

    expect($xml)->not->toBeFalse();

    $day = $xml->Cube->Cube;

    expect((string) $day['time'])->toMatch('/^\d{4}-\d{2}-\d{2}$/')
        ->and(count($day->Cube))->toBeGreaterThan(0);
});

it('contains only valid JSON, XML or RSS', function () {
    $root = dirname(__DIR__, 2);

    libxml_use_internal_errors(true);

    foreach (fixtureFiles() as $path) {
        $contents = (string) file_get_contents("{$root}/{$path}");

        $valid = match (pathinfo($path, PATHINFO_EXTENSION)) {
            'json' => json_validate($contents),
            'xml', 'rss' => (new DOMDocument)->loadXML($contents, LIBXML_NONET),
            default => false,
        };

        expect($valid)->toBeTrue("Not valid JSON, XML or RSS: {$path}");
    }
});

it('tracks every fixture in git', function () {
    $root = dirname(__DIR__, 2);
    exec('git -C '.escapeshellarg($root).' ls-files -- tests/Fixtures', $tracked, $exitCode);

    expect($exitCode)->toBe(0)
        ->and(array_diff(fixtureFiles(), $tracked))->toBe([]);
});
