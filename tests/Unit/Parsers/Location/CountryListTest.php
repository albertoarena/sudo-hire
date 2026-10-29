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

it('gives unknown when one name of the list is not a country (not from a fixture)', function () {
    expect((new LocationParser)->countryList(['Spain', 'Barcelona'])->type)->toBe(EligibilityType::Unknown);
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
