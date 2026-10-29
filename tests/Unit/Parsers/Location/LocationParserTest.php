<?php

use App\Parsers\Location\LocationParser;
use App\Parsers\Location\ParsedLocation;
use App\Schema\EligibilityType;
use App\Schema\WorkMode;

/**
 * @param  list<string>  $regions
 * @param  list<string>  $countries
 */
function expectLocation(ParsedLocation $result, string $mode, string $type, array $regions, array $countries, ?float $min, ?float $max, ?string $office): void
{
    expect($result->workMode)->toBe(WorkMode::from($mode))
        ->and($result->eligibility->type)->toBe(EligibilityType::from($type))
        ->and($result->eligibility->regions)->toBe($regions)
        ->and($result->eligibility->countries)->toBe($countries)
        ->and($result->timezoneMin)->toBe($min)
        ->and($result->timezoneMax)->toBe($max)
        ->and($result->officeLocation)->toBe($office);
}

it('reads the real location strings of the fixtures', function (string $file, string $text, string $mode, string $type, array $regions, array $countries, ?float $min, ?float $max, ?string $office) {
    $raw = (string) file_get_contents(dirname(__DIR__, 3).'/Fixtures/'.$file);
    $escaped = substr((string) json_encode($text), 1, -1);

    expect(str_contains($raw, $text) || str_contains($raw, $escaped))->toBeTrue("\"{$text}\" is not in {$file}");

    expectLocation((new LocationParser)->parse($text), $mode, $type, $regions, $countries, $min, $max, $office);
})->with(require __DIR__.'/location-table.php');

// Not from a fixture: no source gives a timezone-only location field
// (checked on 9 sources, see docs/DECISIONS.md). These rows test the rules.
it('reads timezone-only text into the timezone fields, never a region (not from a fixture)', function (string $text, float $min, float $max) {
    expectLocation((new LocationParser)->parse($text), 'unknown', 'unknown', [], [], $min, $max, null);
})->with([
    'CET' => ['CET', 1.0, 2.0],
    'UTC offset with spread' => ['UTC+1 ±2', -1.0, 3.0],
    'GMT range' => ['GMT-3 to GMT+3', -3.0, 3.0],
    'half hour offset' => ['UTC+5:30', 5.5, 5.5],
    'country timezones' => ['USA timezones', -10.0, -4.0],
    'IANA zone' => ['Europe/Berlin', 1.0, 2.0],
    'Eastern time' => ['EST', -5.0, -4.0],
]);

it('never reads a region from timezone text it cannot read (not from a fixture)', function () {
    expectLocation((new LocationParser)->parse('European time zones'), 'unknown', 'unknown', [], [], null, null, null);
});

it('keeps timezone offsets decimal and inside -12 to 14 (not from a fixture)', function (string $text, float $min, float $max) {
    $result = (new LocationParser)->parse($text);

    expect($result->timezoneMin)->toBe($min)
        ->and($result->timezoneMax)->toBe($max)
        ->and($result->timezoneMin)->toBeGreaterThanOrEqual(-12)
        ->and($result->timezoneMax)->toBeLessThanOrEqual(14);
})->with([
    ['UTC-11 ±3', -12.0, -8.0],
    ['UTC+13 ±3', 10.0, 14.0],
    ['UTC+5:45', 5.75, 5.75],
    ['UTC-9:30', -9.5, -9.5],
]);

it('gives unknown eligibility when the text excludes places (not from a fixture)', function (string $text) {
    expect((new LocationParser)->parse($text)->eligibility->type)->toBe(EligibilityType::Unknown);
})->with(['Remote, except USA', 'Worldwide, excluding Russia', 'Anywhere but not UK']);

it('reads the remote parts only when one part is remote and another is in an office (not from a fixture)', function () {
    expectLocation((new LocationParser)->parse('Remote, Spain; Hybrid, Berlin'), 'remote', 'restricted', [], ['ES'], null, null, null);
});

it('keeps the office text of every hybrid or on-site part (not from a fixture)', function () {
    expectLocation((new LocationParser)->parse('London, UK (Hybrid); On-site, Stockholm'), 'hybrid', 'unknown', [], [], null, null, 'London, UK; Stockholm');
});

it('does not read a country name that is also a US state in free text (not from a fixture)', function () {
    expect((new LocationParser)->parse('Atlanta, Georgia')->eligibility->type)->toBe(EligibilityType::Unknown)
        ->and((new LocationParser)->countryList(['Georgia'])->countries)->toBe(['GE']);
});

it('gives places priority over worldwide words (not from a fixture)', function () {
    expectLocation((new LocationParser)->parse('Anywhere in Europe'), 'remote', 'restricted', ['europe'], [], null, null, null);
});
