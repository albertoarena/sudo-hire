<?php

use App\Currency\DisplayCurrency;
use App\Models\StoredSettings;
use App\Schema\ContractType;
use App\Schema\WorkMode;
use App\Settings\Settings;
use App\Settings\SettingsStore;
use App\Settings\SystemTimezone;
use App\Settings\UnknownEligibilityMode;
use App\Sources\SourceRegistry;
use App\Sources\UnknownSource;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * A system timezone that always gives $name.
 */
function fakeTimezone(string $name): SystemTimezone
{
    return new class($name) extends SystemTimezone
    {
        public function __construct(private readonly string $fake) {}

        public function name(): string
        {
            return $this->fake;
        }
    };
}

function settingsStore(string $timezone = 'Europe/Rome'): SettingsStore
{
    return new SettingsStore(fakeTimezone($timezone), new SourceRegistry);
}

it('gives the defaults with the system timezone before the first save', function () {
    expect(settingsStore('Europe/Rome')->load())->toEqual(Settings::defaults('Europe/Rome'));
});

it('loads the settings it saved', function () {
    $settings = Settings::fromArray(array_merge(Settings::defaults('Europe/Rome')->toArray(), [
        'country' => 'IT',
        'workModes' => [WorkMode::Remote->value, WorkMode::Hybrid->value],
        'unknownEligibility' => UnknownEligibilityMode::Strict->value,
        'keywordsInclude' => ['Laravel', 'PHP'],
        'minAnnualSalary' => 55000.5,
        'contractTypes' => [ContractType::FullTime->value],
        'disabledSources' => ['remotive'],
        'displayCurrency' => DisplayCurrency::Gbp->value,
        'monthlyPayments' => 14,
    ]));

    settingsStore()->save($settings);

    // A new store with another system timezone: the saved timezone wins.
    expect(settingsStore('America/New_York')->load())->toEqual($settings);
});

it('keeps one row when it saves again', function () {
    $store = settingsStore();
    $store->save(Settings::defaults('Europe/Rome'));
    $store->save(Settings::fromArray(['country' => 'DE', 'timezone' => 'Europe/Berlin']));

    expect(StoredSettings::query()->count())->toBe(1)
        ->and($store->load()->country)->toBe('DE');
});

it('rejects a disabled source that the registry does not know', function () {
    settingsStore()->save(Settings::fromArray(['disabledSources' => ['remoteok']]));
})->throws(UnknownSource::class, 'Unknown source "remoteok".');

it('stores nothing when it rejects the settings', function () {
    try {
        settingsStore()->save(Settings::fromArray(['disabledSources' => ['remoteok']]));
    } catch (UnknownSource) {
    }

    expect(StoredSettings::query()->count())->toBe(0);
});

it('can be built by the container', function () {
    expect(app(SettingsStore::class))->toBeInstanceOf(SettingsStore::class);
});

it('marks the next scan as the first scan when a country is saved for the first time', function () {
    $store = settingsStore();
    $defaults = Settings::defaults('Europe/Rome');

    expect($store->firstScanPending())->toBeFalse();

    $store->save($defaults);
    expect($store->firstScanPending())->toBeFalse();

    $store->save(Settings::fromArray(array_merge($defaults->toArray(), ['country' => 'IT'])));
    expect($store->firstScanPending())->toBeTrue();

    $store->firstScanDone();
    expect($store->firstScanPending())->toBeFalse();

    // Another country later is not a first scan.
    $store->save(Settings::fromArray(array_merge($defaults->toArray(), ['country' => 'DE'])));
    expect($store->firstScanPending())->toBeFalse();
});

it('marks the first scan also when the first save has a country', function () {
    $store = settingsStore();

    $store->save(Settings::fromArray(array_merge(Settings::defaults('Europe/Rome')->toArray(), ['country' => 'IT'])));

    expect($store->firstScanPending())->toBeTrue()
        ->and($store->load()->country)->toBe('IT');
});
