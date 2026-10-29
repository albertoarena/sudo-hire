<?php

use App\Schema\Eligibility;
use App\Schema\EligibilityType;
use App\Schema\InvalidJob;

it('builds from valid data', function () {
    $eligibility = Eligibility::fromArray([
        'type' => 'restricted',
        'regions' => ['eu', 'emea'],
        'countries' => ['IT', 'DE'],
    ]);

    expect($eligibility->type)->toBe(EligibilityType::Restricted)
        ->and($eligibility->regions)->toBe(['eu', 'emea'])
        ->and($eligibility->countries)->toBe(['IT', 'DE']);
});

it('builds worldwide and unknown without regions or countries', function () {
    expect(Eligibility::worldwide()->type)->toBe(EligibilityType::Worldwide)
        ->and(Eligibility::unknown()->type)->toBe(EligibilityType::Unknown)
        ->and(Eligibility::fromArray(['type' => 'worldwide'])->regions)->toBe([]);
});

it('rejects an invalid type', function () {
    Eligibility::fromArray(['type' => 'anywhere']);
})->throws(InvalidJob::class);

it('rejects a country that is not ISO 3166-1 alpha-2', function (string $country) {
    Eligibility::restricted(countries: [$country]);
})->throws(InvalidJob::class)->with(['it', 'ITA', 'Italy', '']);

it('rejects a region that is not a lowercase key', function (string $region) {
    Eligibility::restricted(regions: [$region]);
})->throws(InvalidJob::class)->with(['Europe', 'north america', '']);

it('rejects restricted without regions or countries', function () {
    Eligibility::restricted();
})->throws(InvalidJob::class);

it('rejects worldwide or unknown with regions or countries', function (string $type) {
    Eligibility::fromArray(['type' => $type, 'countries' => ['IT']]);
})->throws(InvalidJob::class)->with(['worldwide', 'unknown']);

it('removes duplicate regions and countries', function () {
    $eligibility = Eligibility::restricted(regions: ['eu', 'eu'], countries: ['IT', 'IT']);

    expect($eligibility->regions)->toBe(['eu'])
        ->and($eligibility->countries)->toBe(['IT']);
});
