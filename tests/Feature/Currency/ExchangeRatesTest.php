<?php

use App\Currency\ExchangeRates;
use App\Models\ExchangeRate;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\Support\JobFields;

uses(RefreshDatabase::class);

function fakeEcb(): void
{
    Http::fake(['www.ecb.europa.eu/*' => Http::response(JobFields::fixture('ecb/eurofxref-daily.xml'))]);
}

it('creates the exchange_rates table', function () {
    expect(Schema::hasColumns('exchange_rates', ['currency', 'rate', 'rate_date', 'fetched_at']))->toBeTrue();
});

it('has no rates and no rate date before the first fetch', function () {
    $rates = app(ExchangeRates::class);

    expect($rates->rateDate())->toBeNull()
        ->and($rates->converter()->convert(100, 'EUR', 'USD'))->toBeNull()
        ->and($rates->converter()->convert(100, 'EUR', 'EUR'))->toBe(100.0);
});

it('stores the ECB rates and their date', function () {
    fakeEcb();

    expect(app(ExchangeRates::class)->refresh(CarbonImmutable::parse('2026-10-29T10:00:00Z')))->toBeTrue();

    expect(ExchangeRate::count())->toBe(29)
        ->and(ExchangeRate::where('currency', 'USD')->value('rate'))->toBe(1.1133)
        ->and(app(ExchangeRates::class)->rateDate()?->toDateString())->toBe('2026-10-28')
        ->and(app(ExchangeRates::class)->converter()->convert(100, 'EUR', 'USD'))->toEqualWithDelta(111.33, 0.0001);
});

it('makes no request on a second fetch on the same day', function () {
    fakeEcb();
    $rates = app(ExchangeRates::class);

    $rates->refresh(CarbonImmutable::parse('2026-10-29T08:00:00Z'));

    expect($rates->refresh(CarbonImmutable::parse('2026-10-29T20:00:00Z')))->toBeFalse();

    Http::assertSentCount(1);
});

it('fetches again on the next day', function () {
    fakeEcb();
    $rates = app(ExchangeRates::class);

    $rates->refresh(CarbonImmutable::parse('2026-10-29T08:00:00Z'));

    expect($rates->refresh(CarbonImmutable::parse('2026-10-30T08:00:00Z')))->toBeTrue();

    Http::assertSentCount(2);
});

it('keeps the old rates when a fetch fails', function () {
    Http::fake(['www.ecb.europa.eu/*' => Http::sequence()
        ->push(JobFields::fixture('ecb/eurofxref-daily.xml'))
        ->push('Service Unavailable', 503)]);
    $rates = app(ExchangeRates::class);
    $rates->refresh(CarbonImmutable::parse('2026-10-29T08:00:00Z'));

    expect($rates->refresh(CarbonImmutable::parse('2026-10-30T08:00:00Z')))->toBeFalse()
        ->and(ExchangeRate::count())->toBe(29)
        ->and($rates->rateDate()?->toDateString())->toBe('2026-10-28')
        ->and($rates->converter()->convert(100, 'EUR', 'GBP'))->toEqualWithDelta(83.785, 0.0001);

    Http::assertSentCount(2);
});

it('replaces the old rates with the new ones', function () {
    ExchangeRate::create(['currency' => 'XYZ', 'rate' => 2.0, 'rate_date' => '2026-10-27', 'fetched_at' => '2026-10-28 08:00:00']);
    fakeEcb();

    app(ExchangeRates::class)->refresh(CarbonImmutable::parse('2026-10-29T08:00:00Z'));

    expect(ExchangeRate::where('currency', 'XYZ')->exists())->toBeFalse()
        ->and(ExchangeRate::count())->toBe(29);
});
