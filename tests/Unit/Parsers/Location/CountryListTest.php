<?php

use App\Parsers\Location\LocationParser;
use App\Schema\EligibilityType;

const LOCATION_FIXTURES = __DIR__.'/../../../Fixtures';

/**
 * @return list<list<string>> the locationRestrictions lists of a Himalayas fixture that are not empty
 */
function himalayasRestrictions(string $file): array
{
    $data = json_decode((string) file_get_contents(LOCATION_FIXTURES."/himalayas/{$file}"), true);

    return array_values(array_filter(array_map(fn (array $job) => $job['locationRestrictions'], $data['jobs'])));
}

it('reads the Himalayas locationRestrictions lists', function (string $file, array $expected) {
    $results = array_map(
        fn (array $names) => (new LocationParser)->countryList($names),
        himalayasRestrictions($file),
    );

    foreach ($results as $result) {
        expect($result->type)->toBe(EligibilityType::Restricted)
            ->and($result->regions)->toBe([]);
    }

    expect(array_map(fn ($result) => $result->countries, $results))->toBe($expected);
})->with([
    'himalayas/jobs.json' => ['jobs.json', [['US'], ['DE', 'IT'], ['GB']]],
    'himalayas/search-mid.json' => ['search-mid.json', [['ES'], ['US']]],
    'himalayas/search.json' => ['search.json', [['BG'], ['RS']]],
]);

it('reads the We Work Remotely country list with flag emoji', function () {
    $xml = simplexml_load_file(LOCATION_FIXTURES.'/weworkremotely/back-end.rss');
    $country = (string) $xml->channel->item[0]->country;

    $result = (new LocationParser)->countryText($country);

    expect($result->type)->toBe(EligibilityType::Restricted)
        ->and($result->regions)->toBe([])
        ->and($result->countries)->toBe([
            'AD', 'AL', 'AT', 'BA', 'BG', 'CH', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GB', 'GR', 'HR',
            'HU', 'IT', 'LT', 'MD', 'ME', 'MK', 'NL', 'NO', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK', 'UA',
        ]);
});

it('skips a name that is not a country and keeps the rest of the list (not from a fixture)', function () {
    $result = (new LocationParser)->countryList(['Spain', 'Atlantis', 'Italy']);

    expect($result->type)->toBe(EligibilityType::Restricted)
        ->and($result->countries)->toBe(['ES', 'IT']);
});

it('skips a name that is not a country in a country text (not from a fixture)', function () {
    $result = (new LocationParser)->countryText('🇪🇸 Spain, Atlantis, and 🇮🇹 Italy');

    expect($result->type)->toBe(EligibilityType::Restricted)
        ->and($result->countries)->toBe(['ES', 'IT']);
});

it('gives unknown when no name of the list is a country (not from a fixture)', function () {
    expect((new LocationParser)->countryList(['Atlantis', 'Barcelona'])->type)->toBe(EligibilityType::Unknown)
        ->and((new LocationParser)->countryText('Atlantis, Barcelona')->type)->toBe(EligibilityType::Unknown);
});

it('reads the Himalayas Latin America list with its ISO style names (live item, 2026-11-02)', function () {
    $names = [
        'Anguilla', 'Antigua and Barbuda', 'Argentina', 'Aruba', 'Bahamas', 'Barbados', 'Belize', 'Bermuda', 'Bolivia',
        'Bonaire, Sint Eustatius and Saba', 'Brazil', 'Cayman Islands', 'Chile', 'Colombia', 'Costa Rica', 'Cuba', 'Dominica',
        'Dominican Republic', 'Ecuador', 'El Salvador', 'Falkland Islands (Malvinas)', 'Grenada', 'Guatemala', 'Guyana', 'Haiti',
        'Honduras', 'Jamaica', 'Mexico', 'Montserrat', 'Nicaragua', 'Panama', 'Paraguay', 'Peru', 'Puerto Rico',
        'Saint Kitts and Nevis', 'Saint Lucia', 'Saint Vincent and the Grenadines', 'South Georgia and the South Sandwich Islands',
        'Suriname', 'Trinidad and Tobago', 'Turks and Caicos Islands', 'Uruguay', 'Venezuela', 'Virgin Islands, British',
        'Virgin Islands, U.S.',
    ];

    $result = (new LocationParser)->countryList($names);

    expect($result->type)->toBe(EligibilityType::Restricted)
        ->and($result->countries)->toHaveCount(45)
        ->and($result->countries)->toContain('AR', 'BQ', 'FK', 'GS', 'MX', 'VG', 'VI');
});

it('reads the other ISO style names of Himalayas (live names, 2026-11-02)', function () {
    expect((new LocationParser)->countryList(['Holy See (Vatican City State)', 'Congo, The Democratic Republic of the'])->countries)
        ->toBe(['VA', 'CD']);
});

it('skips Kosovo, which has no ISO 3166-1 code (live name, 2026-11-02)', function () {
    expect((new LocationParser)->countryList(['Kosovo', 'Serbia'])->countries)->toBe(['RS']);
});

it('gives unknown for an empty list, so the source decides (not from a fixture)', function () {
    expect((new LocationParser)->countryList([])->type)->toBe(EligibilityType::Unknown)
        ->and((new LocationParser)->countryText('')->type)->toBe(EligibilityType::Unknown);
});

it('reads region names in a list (not from a fixture)', function () {
    $result = (new LocationParser)->countryList(['European Union', 'Switzerland']);

    expect($result->regions)->toBe(['eu'])
        ->and($result->countries)->toBe(['CH']);
});
