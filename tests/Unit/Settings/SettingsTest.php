<?php

use App\Schema\ContractType;
use App\Schema\Seniority;
use App\Schema\WorkMode;
use App\Settings\DisplayCurrency;
use App\Settings\InvalidSettings;
use App\Settings\Settings;
use App\Settings\UnknownEligibilityMode;
use App\Settings\UnknownSalaryMode;

it('has the defaults of REQUIREMENTS section 8', function () {
    $settings = Settings::defaults('Europe/Rome');

    expect($settings->country)->toBeNull()
        ->and($settings->timezone)->toBe('Europe/Rome')
        ->and($settings->workModes)->toBe([WorkMode::Remote])
        ->and($settings->unknownEligibility)->toBe(UnknownEligibilityMode::Group)
        ->and($settings->timezoneOverlapHours)->toBeNull()
        ->and($settings->keywordsInclude)->toBe([])
        ->and($settings->keywordsExclude)->toBe([])
        ->and($settings->matchDescription)->toBeFalse()
        ->and($settings->minAnnualSalary)->toBeNull()
        ->and($settings->minDayRate)->toBeNull()
        ->and($settings->unknownSalary)->toBe(UnknownSalaryMode::Lenient)
        ->and($settings->contractTypes)->toBe([ContractType::FullTime, ContractType::PartTime, ContractType::Contract])
        ->and($settings->seniorities)->toBe([Seniority::Junior, Seniority::Mid, Seniority::Senior, Seniority::Lead])
        ->and($settings->postedWithinHours)->toBe(72)
        ->and($settings->companyBlocklist)->toBe([])
        ->and($settings->disabledSources)->toBe([])
        ->and($settings->displayCurrency)->toBe(DisplayCurrency::Eur)
        ->and($settings->monthlyPayments)->toBe(12)
        ->and($settings->hoursPerDay)->toBe(8)
        ->and($settings->notificationIntervalHours)->toBe(2);
});

it('gives the same settings back from toArray', function () {
    $settings = Settings::fromArray([
        'country' => 'IT',
        'timezone' => 'Europe/Rome',
        'workModes' => ['remote', 'hybrid'],
        'unknownEligibility' => 'strict',
        'timezoneOverlapHours' => 3,
        'keywordsInclude' => ['Laravel', 'PHP'],
        'keywordsExclude' => ['WordPress'],
        'matchDescription' => true,
        'minAnnualSalary' => 60000,
        'minDayRate' => 400,
        'unknownSalary' => 'strict',
        'contractTypes' => ['full_time', 'contract'],
        'seniorities' => ['senior', 'lead'],
        'postedWithinHours' => 24,
        'companyBlocklist' => ['Acme'],
        'disabledSources' => ['jobicy'],
        'displayCurrency' => 'GBP',
        'monthlyPayments' => 14,
        'hoursPerDay' => 7,
        'notificationIntervalHours' => 6,
    ]);

    expect($settings->workModes)->toBe([WorkMode::Remote, WorkMode::Hybrid])
        ->and($settings->minAnnualSalary)->toBe(60000.0)
        ->and($settings->displayCurrency)->toBe(DisplayCurrency::Gbp)
        ->and(Settings::fromArray($settings->toArray()))->toEqual($settings);
});

it('uses the default for each missing key', function () {
    $settings = Settings::fromArray(['country' => 'IT', 'timezone' => 'Europe/Rome']);

    expect($settings)->toEqual(Settings::fromArray(array_merge(
        Settings::defaults('Europe/Rome')->toArray(),
        ['country' => 'IT'],
    )));
});

it('uses UTC when the timezone is missing', function () {
    expect(Settings::fromArray([])->timezone)->toBe('UTC');
});

it('trims the lists and removes empty and repeated entries', function () {
    $settings = Settings::fromArray([
        'keywordsInclude' => [' Laravel ', '', 'PHP', 'laravel'],
        'companyBlocklist' => ['Acme', ' ', 'Acme'],
    ]);

    expect($settings->keywordsInclude)->toBe(['Laravel', 'PHP'])
        ->and($settings->companyBlocklist)->toBe(['Acme']);
});

it('rejects invalid values', function (array $data) {
    Settings::fromArray($data);
})->throws(InvalidSettings::class)->with([
    'lower case country' => [['country' => 'it']],
    'three letter country' => [['country' => 'ITA']],
    'unknown timezone' => [['timezone' => 'Europe/Atlantis']],
    'no work mode' => [['workModes' => []]],
    'unknown work mode' => [['workModes' => ['unknown']]],
    'invalid work mode' => [['workModes' => ['office']]],
    'invalid unknown eligibility mode' => [['unknownEligibility' => 'hide']],
    'negative timezone overlap' => [['timezoneOverlapHours' => -1]],
    'timezone overlap over 12' => [['timezoneOverlapHours' => 13]],
    'zero min annual salary' => [['minAnnualSalary' => 0]],
    'negative min day rate' => [['minDayRate' => -400]],
    'invalid unknown salary mode' => [['unknownSalary' => 'group']],
    'no contract type' => [['contractTypes' => []]],
    'unknown contract type' => [['contractTypes' => ['unknown']]],
    'no seniority' => [['seniorities' => []]],
    'unknown seniority' => [['seniorities' => ['unknown']]],
    'posted within 48 hours' => [['postedWithinHours' => 48]],
    'display currency CHF' => [['displayCurrency' => 'CHF']],
    '15 monthly payments' => [['monthlyPayments' => 15]],
    'zero hours per day' => [['hoursPerDay' => 0]],
    '25 hours per day' => [['hoursPerDay' => 25]],
    'zero notification interval' => [['notificationIntervalHours' => 0]],
    'notification interval over 24' => [['notificationIntervalHours' => 25]],
]);
