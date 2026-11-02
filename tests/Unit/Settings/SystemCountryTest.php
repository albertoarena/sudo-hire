<?php

use App\Settings\SystemCountry;

it('reads the country from the region of the Mac', function (string $locale, string $country) {
    expect(SystemCountry::fromLocale($locale))->toBe($country);
})->with([
    'with currency' => ['en_IT@currency=eur', 'IT'],
    'plain' => ['it_IT', 'IT'],
    'another language' => ['en_US', 'US'],
    'United Kingdom' => ['en_GB', 'GB'],
    'script' => ['zh-Hans_CN', 'CN'],
    'script and currency' => ['sr-Latn_RS@currency=rsd', 'RS'],
    'region override' => ['en_GB@rg=itzzzz', 'IT'],
    'region override and currency' => ['en_US@currency=eur;rg=dezzzz', 'DE'],
    'with spaces' => ["  de_DE\n", 'DE'],
]);

it('gives no country for a region that is not in the table', function (string $locale) {
    expect(SystemCountry::fromLocale($locale))->toBeNull();
})->with([
    'empty' => [''],
    'language only' => ['en'],
    'world' => ['en_001'],
    'Latin America' => ['es_419'],
    'unknown code' => ['en_ZZ'],
    'not a locale' => ['The domain/default pair of (kCFPreferencesAnyApplication, AppleLocale) does not exist'],
]);
