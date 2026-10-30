<?php

use App\Currency\EcbRatesClient;
use App\Currency\RatesFetchFailed;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Support\JobFields;

it('reads the rates and their date from the ECB daily XML', function () {
    Http::fake(['www.ecb.europa.eu/*' => Http::response(JobFields::fixture('ecb/eurofxref-daily.xml'))]);

    $rates = (new EcbRatesClient)->fetch();

    expect($rates->date->toDateString())->toBe('2026-10-28')
        ->and($rates->rates)->toHaveCount(29)
        ->and($rates->rates['USD'])->toBe(1.1133)
        ->and($rates->rates['GBP'])->toBe(0.83785)
        ->and($rates->rates['JPY'])->toBe(175.86)
        ->and($rates->rates['IDR'])->toBe(20235.79)
        ->and($rates->rates)->not->toHaveKey('EUR');

    Http::assertSent(fn (Request $request) => $request->url() === 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml');
});

it('fails on an HTTP error', function () {
    Http::fake(['www.ecb.europa.eu/*' => Http::response('Service Unavailable', 503)]);

    (new EcbRatesClient)->fetch();
})->throws(RatesFetchFailed::class);

it('fails on a connection error', function () {
    Http::fake(['www.ecb.europa.eu/*' => Http::failedConnection()]);

    (new EcbRatesClient)->fetch();
})->throws(RatesFetchFailed::class);

it('fails on a body that is not the rates XML', function (string $body) {
    Http::fake(['www.ecb.europa.eu/*' => Http::response($body)]);

    (new EcbRatesClient)->fetch();
})->throws(RatesFetchFailed::class)->with([
    'HTML' => ['<html><body>Maintenance</body></html>'],
    'not XML' => ['rates'],
    'no rates' => ["<?xml version=\"1.0\"?><gesmes:Envelope xmlns:gesmes=\"http://www.gesmes.org/xml/2002-08-01\" xmlns=\"http://www.ecb.int/vocabulary/2002-08-01/eurofxref\"><Cube><Cube time='2026-10-28'></Cube></Cube></gesmes:Envelope>"],
]);
