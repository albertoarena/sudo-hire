<?php

use App\Parsers\Location\Countries;
use App\Parsers\Location\Regions;
use App\Schema\Eligibility;

it('has regions for every country in the country table', function () {
    $codes = Regions::countries();
    sort($codes);

    $countries = Countries::codes();
    sort($countries);

    expect($codes)->toBe($countries);
});

it('uses region keys that pass the Eligibility region rule', function () {
    foreach (Regions::keys() as $key) {
        expect(Eligibility::restricted(regions: [$key])->regions)->toBe([$key]);
    }

    expect(Regions::keys())->toContain('eu', 'europe', 'emea', 'north_america', 'latam', 'apac');
});

it('gives each country only known region keys', function () {
    foreach (Regions::countries() as $code) {
        expect(array_diff(Regions::of($code), Regions::keys()))->toBe([], "{$code} has an unknown region");
    }
});

it('maps countries to their regions', function (string $code, array $regions) {
    expect(Regions::of($code))->toEqualCanonicalizing($regions);
})->with([
    'Italy' => ['IT', ['eu', 'europe', 'emea']],
    'United Kingdom' => ['GB', ['europe', 'emea']],
    'Switzerland' => ['CH', ['europe', 'emea']],
    'United States' => ['US', ['north_america', 'americas']],
    'Canada' => ['CA', ['north_america', 'americas']],
    'Mexico' => ['MX', ['latam', 'americas']],
    'Brazil' => ['BR', ['latam', 'americas']],
    'India' => ['IN', ['asia', 'apac']],
    'Australia' => ['AU', ['oceania', 'apac']],
    'Israel' => ['IL', ['asia', 'middle_east', 'emea']],
    'Egypt' => ['EG', ['africa', 'middle_east', 'emea']],
    'Nigeria' => ['NG', ['africa', 'emea']],
    'Cyprus' => ['CY', ['eu', 'europe', 'asia', 'emea']],
]);

it('has the 27 EU member states in eu', function () {
    $eu = array_values(array_filter(Regions::countries(), fn (string $code) => in_array('eu', Regions::of($code), true)));
    sort($eu);

    expect($eu)->toBe([
        'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GR', 'HR', 'HU',
        'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK',
    ]);
});

it('puts every EU member in europe', function () {
    foreach (Regions::countries() as $code) {
        if (in_array('eu', Regions::of($code), true)) {
            expect(Regions::of($code))->toContain('europe');
        }
    }
});

it('gives no regions for an unknown code', function () {
    expect(Regions::of('XX'))->toBe([]);
});

it('reads region names in text', function (string $text, ?string $key) {
    expect(Regions::key($text))->toBe($key);
})->with([
    ['Europe', 'europe'],
    ['EU', 'eu'],
    ['European Union', 'eu'],
    ['emea', 'emea'],
    ['North America', 'north_america'],
    ['LATAM', 'latam'],
    ['Latin America', 'latam'],
    ['APAC', 'apac'],
    ['Asia-Pacific', 'apac'],
    ['Americas', 'americas'],
    ['the Middle East', 'middle_east'],
    ['European', null],
    ['Berlin', null],
    ['', null],
]);
