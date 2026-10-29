<?php

use App\Parsers\Location\Countries;

/**
 * @return list<string> the codes in iso-3166-1-alpha-2.txt
 */
function isoAlpha2Codes(): array
{
    $lines = array_filter(
        file(__DIR__.'/iso-3166-1-alpha-2.txt', FILE_IGNORE_NEW_LINES) ?: [],
        fn (string $line) => $line !== '' && ! str_starts_with($line, '#'),
    );

    return preg_split('/\s+/', trim(implode(' ', $lines))) ?: [];
}

it('has one entry for every ISO 3166-1 alpha-2 code', function () {
    $codes = Countries::codes();
    sort($codes);

    expect(isoAlpha2Codes())->toHaveCount(249)
        ->and($codes)->toBe(isoAlpha2Codes());
});

it('maps every name to exactly one code', function () {
    $seen = [];

    foreach (Countries::codes() as $code) {
        foreach (Countries::names($code) as $name) {
            $key = Countries::normalise($name);

            expect($seen)->not->toHaveKey($key, "\"{$name}\" is listed twice");
            $seen[$key] = $code;
            expect(Countries::code($name))->toBe($code, "\"{$name}\" does not map back to {$code}");
        }
    }
});

// Every country name in tests/Fixtures, with the file it comes from.
it('maps the country names in the fixtures', function (string $name, string $code) {
    expect(Countries::code($name))->toBe($code);
})->with([
    'greenhouse/jobs.json' => ['India', 'IN'],
    'greenhouse/jobs.json, Canada' => ['Canada', 'CA'],
    'greenhouse/jobs.json, United States' => ['United States', 'US'],
    'greenhouse/jobs.json, Poland' => ['Poland', 'PL'],
    'greenhouse/gitlab-levels.json' => ['United Kingdom', 'GB'],
    'greenhouse/twilio-levels.json' => ['Estonia', 'EE'],
    'ashby/job-board.json addressCountry' => ['Australia', 'AU'],
    'ashby/job-board.json, Spain' => ['Spain', 'ES'],
    'ashby/job-board.json, Italy' => ['Italy', 'IT'],
    'ashby/job-board.json, Switzerland' => ['Switzerland', 'CH'],
    'ashby/job-board.json, Croatia' => ['Croatia', 'HR'],
    'ashby/job-board.json, Ireland' => ['Ireland', 'IE'],
    'ashby/job-board.json, Romania' => ['Romania', 'RO'],
    'ashby/job-board.json, Portugal' => ['Portugal', 'PT'],
    'ashby/job-board.json, Sweden' => ['Sweden', 'SE'],
    'ashby/job-board.json, New Zealand' => ['New Zealand', 'NZ'],
    'ashby/job-board.json, Singapore' => ['Singapore', 'SG'],
    'ashby/job-board.json, South Korea' => ['South Korea', 'KR'],
    'ashby/job-board.json, Japan' => ['Japan', 'JP'],
    'jobicy/remote-jobs.json, Philippines' => ['Philippines', 'PH'],
    'jobicy/remote-jobs.json, USA' => ['USA', 'US'],
    'remotive/remote-jobs.json, UK' => ['UK', 'GB'],
    'remotive/remote-jobs.json, Mexico' => ['Mexico', 'MX'],
    'remotive/remote-jobs.json, Iceland' => ['Iceland', 'IS'],
    'remotive/remote-jobs.json, Norway' => ['Norway', 'NO'],
    'himalayas/jobs.json, Germany' => ['Germany', 'DE'],
    'himalayas/search.json, Bulgaria' => ['Bulgaria', 'BG'],
    'himalayas/search.json, Serbia' => ['Serbia', 'RS'],
    'weworkremotely/back-end.rss, Andorra' => ['🇦🇩 Andorra', 'AD'],
    'weworkremotely/back-end.rss, Albania' => ['🇦🇱 Albania', 'AL'],
    'weworkremotely/back-end.rss, Austria' => ['🇦🇹 Austria', 'AT'],
    'weworkremotely/back-end.rss, Bosnia' => ['🇧🇦 Bosnia and Herzegovina', 'BA'],
    'weworkremotely/back-end.rss, Czechia' => ['🇨🇿 Czechia', 'CZ'],
    'weworkremotely/back-end.rss, Denmark' => ['🇩🇰 Denmark', 'DK'],
    'weworkremotely/back-end.rss, Finland' => ['🇫🇮 Finland', 'FI'],
    'weworkremotely/back-end.rss, France' => ['🇫🇷 France', 'FR'],
    'weworkremotely/back-end.rss, UK long name' => ['🇬🇧 United Kingdom of Great Britain and Northern Ireland', 'GB'],
    'weworkremotely/back-end.rss, Greece' => ['🇬🇷 Greece', 'GR'],
    'weworkremotely/back-end.rss, Hungary' => ['🇭🇺 Hungary', 'HU'],
    'weworkremotely/back-end.rss, Lithuania' => ['🇱🇹 Lithuania', 'LT'],
    'weworkremotely/back-end.rss, Moldova' => ['🇲🇩 Moldova (Republic of)', 'MD'],
    'weworkremotely/back-end.rss, Montenegro' => ['🇲🇪 Montenegro', 'ME'],
    'weworkremotely/back-end.rss, North Macedonia' => ['🇲🇰 North Macedonia', 'MK'],
    'weworkremotely/back-end.rss, Netherlands' => ['🇳🇱 Netherlands', 'NL'],
    'weworkremotely/back-end.rss, Slovenia' => ['🇸🇮 Slovenia', 'SI'],
    'weworkremotely/back-end.rss, Slovakia' => ['🇸🇰 Slovakia', 'SK'],
    'weworkremotely/back-end.rss, Ukraine' => ['🇺🇦 Ukraine', 'UA'],
]);

it('gives the same code with or without a flag emoji', function (string $flag, string $name) {
    expect(Countries::code("{$flag} {$name}"))->toBe(Countries::code($name))
        ->and(Countries::code($name))->not->toBeNull();
})->with([
    ['🇮🇹', 'Italy'],
    ['🇬🇧', 'United Kingdom of Great Britain and Northern Ireland'],
    ['🇲🇩', 'Moldova (Republic of)'],
]);

it('ignores case, dots and extra spaces', function (string $name, string $code) {
    expect(Countries::code($name))->toBe($code);
})->with([
    ['  united   kingdom ', 'GB'],
    ['U.S.A.', 'US'],
    ['u.s.', 'US'],
    ['The Netherlands', 'NL'],
    ['Bosnia & Herzegovina', 'BA'],
]);

it('gives null for text that is not a country name', function (string $name) {
    expect(Countries::code($name))->toBeNull();
})->with(['', 'Remote', 'Bangalore', 'European Union', 'CA', 'NY', 'Moldova (somewhere)']);
