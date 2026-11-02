<?php

use App\Parsers\Location\TitleLocationParser;
use App\Schema\Eligibility;
use App\Schema\EligibilityType;
use Tests\Support\FixtureTitles;

/**
 * @param  list<string>  $regions
 * @param  list<string>  $countries
 */
function expectEligibility(Eligibility $result, string $type, array $regions = [], array $countries = []): void
{
    expect($result->type)->toBe(EligibilityType::from($type))
        ->and($result->regions)->toBe($regions)
        ->and($result->countries)->toBe($countries);
}

// Short inline titles, as the brief of the title limits fix asks.
// See "Location limits in the title" in docs/DECISIONS.md.
it('reads a location limit from a title', function (string $title, array $regions, array $countries) {
    expectEligibility((new TitleLocationParser)->parse($title), 'restricted', $regions, $countries);
})->with([
    'remote eligible in a country' => ['Principal Software Engineer (Remote Eligible in Bulgaria)', [], ['BG']],
    'remote dash country' => ['Remote - Germany', [], ['DE']],
    'country only' => ['Laravel Engineer (US only)', [], ['US']],
    'region code in brackets' => ['Senior PHP Developer (LATAM)', ['latam'], []],
    'remote comma region code' => ['Remote, EMEA', ['emea'], []],
    'remote and region name in brackets' => ['PHP Developer (Remote, Europe)', ['europe'], []],
    'country based' => ['Backend Engineer - Spain based', [], ['ES']],
    'remote word in the title, country after a dash' => ['Remote PHP Developer - Portugal', [], ['PT']],
    'region code after a dash' => ['Product Support Engineer - APAC', ['apac'], []],
    'two places in brackets' => ['Laravel Developer (Remote, Germany or Austria)', [], ['DE', 'AT']],
]);

it('reads nothing from a title with no clear limit', function (string $title) {
    expectEligibility((new TitleLocationParser)->parse($title), 'unknown');
})->with([
    'global in a team name' => ['Global Payments Engineer'],
    'no place' => ['Laravel Developer'],
    'team after a comma' => ['Backend Engineer, Payments'],
    'company name with a country word' => ['PHP Developer, Bank of Ireland (Remote)'],
    'country word inside a name' => ['Remote Engineer - New Zealand Post'],
    'country with no limit word' => ['Product Support Specialist - Australia'],
    'country before the role' => ['Germany Sales Lead (Remote)'],
    'region inside a role name' => ['European Markets Analyst'],
    'worldwide' => ['Laravel Developer (Remote, Worldwide)'],
    'exclusion' => ['Laravel Developer (Remote, not US)'],
    'hybrid office' => ['Laravel Developer (Hybrid, Germany)'],
    'ambiguous US state' => ['Remote Developer - Georgia'],
    'timezone only' => ['Senior Fullstack PHP/Laravel Developer – Remote (Europe/Berlin Time Zone)'],
]);

it('reads the real titles of the fixtures', function (string $file, string $title, array $regions, array $countries) {
    $titles = array_map(fn (array $row) => $row[1], array_filter(FixtureTitles::all(), fn (array $row) => $row[0] === $file));

    expect($titles)->toContain($title);

    expectEligibility((new TitleLocationParser)->parse($title), 'restricted', $regions, $countries);
})->with(require __DIR__.'/title-location-table.php');

it('reads nothing from every other fixture title', function () {
    $limited = array_map(fn (array $row) => [$row[0], $row[1]], array_values(require __DIR__.'/title-location-table.php'));

    foreach (FixtureTitles::all() as $row) {
        if (! in_array($row, $limited, true)) {
            expect((new TitleLocationParser)->parse($row[1])->type)->toBe(EligibilityType::Unknown, "\"{$row[1]}\" in {$row[0]}");
        }
    }
});

describe('narrowing', function () {
    it('keeps the source eligibility when the title names no place', function (Eligibility $source) {
        expect((new TitleLocationParser)->narrow($source, 'Laravel Developer'))->toEqual($source);
    })->with([
        'worldwide' => [Eligibility::worldwide()],
        'unknown' => [Eligibility::unknown()],
        'restricted' => [Eligibility::restricted([], ['US'])],
    ]);

    it('takes the title places when the source says worldwide', function () {
        expectEligibility((new TitleLocationParser)->narrow(Eligibility::worldwide(), 'Principal Software Engineer (Remote Eligible in Bulgaria)'), 'restricted', [], ['BG']);
    });

    it('takes the title places when the source says unknown', function () {
        expectEligibility((new TitleLocationParser)->narrow(Eligibility::unknown(), 'Senior PHP Developer (LATAM)'), 'restricted', ['latam']);
    });

    it('keeps the places of both lists', function (Eligibility $source, string $title, array $regions, array $countries) {
        expectEligibility((new TitleLocationParser)->narrow($source, $title), 'restricted', $regions, $countries);
    })->with([
        'countries in a title region' => [Eligibility::restricted([], ['BG', 'DE', 'US']), 'PHP Developer (Remote, Europe)', [], ['BG', 'DE']],
        'a title country in a source region' => [Eligibility::restricted(['europe']), 'Principal Software Engineer (Remote Eligible in Bulgaria)', [], ['BG']],
        'a title region inside a source region' => [Eligibility::restricted(['emea']), 'Senior Software Engineer, Product Engineering - EU', ['eu'], []],
        'a source region inside a title region' => [Eligibility::restricted(['eu']), 'Remote, EMEA', ['eu'], []],
        'the same country' => [Eligibility::restricted([], ['US']), 'Laravel Engineer (US only)', [], ['US']],
    ]);

    it('gives unknown, not a refusal, when the lists have no place in common', function () {
        expectEligibility((new TitleLocationParser)->narrow(Eligibility::restricted([], ['US']), 'PHP Developer (Remote, Europe)'), 'unknown');
    });
});
