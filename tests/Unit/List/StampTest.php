<?php

use App\Currency\CurrencyConverter;
use App\Filters\JobFilter;
use App\List\Stamp;
use App\Schema\Eligibility;
use Carbon\CarbonImmutable;
use Tests\Unit\Filters\Make;

function stampOf(Eligibility $eligibility, array $settings = ['country' => 'IT']): array
{
    $job = Make::job(['eligibility' => $eligibility, 'postedAt' => CarbonImmutable::parse('2026-11-02T08:00:00Z')]);
    $settings = Make::settings($settings);
    $result = JobFilter::evaluate($job, $settings, new CurrencyConverter([]), CarbonImmutable::parse('2026-11-02T10:00:00Z'));

    return Stamp::of($job, $settings, $result)->toArray();
}

it('gives the country code of the user when the job lists the country', function () {
    expect(stampOf(Eligibility::restricted(['europe'], ['DE', 'IT'])))->toBe(['kind' => 'admitted', 'place' => 'IT']);
});

it('gives the name of the region that admits the job', function (array $regions, string $place) {
    expect(stampOf(Eligibility::restricted($regions, [])))->toBe(['kind' => 'admitted', 'place' => $place]);
})->with([
    'Europe' => [['europe'], 'Europe'],
    'EU' => [['eu'], 'EU'],
    'EMEA' => [['emea'], 'EMEA'],
    'the first region of the job that admits it' => [['north_america', 'emea', 'europe'], 'EMEA'],
]);

it('gives the region names of other countries', function (string $country, string $region, string $place) {
    expect(stampOf(Eligibility::restricted([$region], []), ['country' => $country]))->toBe(['kind' => 'admitted', 'place' => $place]);
})->with([
    ['US', 'north_america', 'North America'],
    ['BR', 'latam', 'LATAM'],
    ['JP', 'apac', 'APAC'],
    ['AE', 'middle_east', 'Middle East'],
    ['NG', 'africa', 'Africa'],
    ['AU', 'oceania', 'Oceania'],
    ['CA', 'americas', 'Americas'],
    ['IN', 'asia', 'Asia'],
]);

it('gives "Location" for a job open worldwide', function () {
    expect(stampOf(Eligibility::worldwide()))->toBe(['kind' => 'admitted', 'place' => 'Location']);
});

it('gives "Location" when the user has no country', function () {
    expect(stampOf(Eligibility::restricted(['europe'], ['IT']), ['country' => null]))->toBe(['kind' => 'admitted', 'place' => 'Location']);
});

it('gives "not stated" for a job in the "Location not stated" group', function () {
    expect(stampOf(Eligibility::unknown()))->toBe(['kind' => 'not_stated', 'place' => 'Location']);
});

it('gives "not stated" for a job with no location in lenient mode', function () {
    expect(stampOf(Eligibility::unknown(), ['country' => 'IT', 'unknownEligibility' => 'lenient']))->toBe(['kind' => 'not_stated', 'place' => 'Location']);
});

it('gives "refused" for a job that does not admit the user, such as a saved job after a change of country', function () {
    expect(stampOf(Eligibility::restricted(['north_america'], ['US'])))->toBe(['kind' => 'refused', 'place' => null]);
});
