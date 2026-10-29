<?php

use App\Currency\CurrencyConverter;
use App\Currency\DisplayCurrency;

beforeEach(function () {
    // Rates for 1 EUR, from tests/Fixtures/ecb/eurofxref-daily.xml
    $this->converter = new CurrencyConverter(['USD' => 1.1133, 'GBP' => 0.83785, 'JPY' => 175.86]);
});

it('converts EUR to USD', function () {
    expect($this->converter->convert(100, 'EUR', 'USD'))->toEqualWithDelta(111.33, 0.0001);
});

it('converts USD to EUR', function () {
    expect($this->converter->convert(111.33, 'USD', 'EUR'))->toEqualWithDelta(100.0, 0.0001);
});

it('converts USD to GBP through EUR', function () {
    expect($this->converter->convert(100, 'USD', 'GBP'))->toEqualWithDelta(100 / 1.1133 * 0.83785, 0.0001);
});

it('keeps the amount when the currencies are the same', function () {
    expect($this->converter->convert(123.45, 'USD', 'USD'))->toBe(123.45)
        ->and($this->converter->convert(123.45, 'EUR', 'EUR'))->toBe(123.45);
});

it('gives null for an unknown currency', function () {
    expect($this->converter->convert(100, 'XYZ', 'EUR'))->toBeNull()
        ->and($this->converter->convert(100, 'EUR', 'XYZ'))->toBeNull()
        ->and($this->converter->convert(100, 'XYZ', 'XYZ'))->toBeNull();
});

it('has the three display currencies', function () {
    expect(array_map(fn (DisplayCurrency $c) => $c->value, DisplayCurrency::cases()))->toBe(['EUR', 'USD', 'GBP']);
});
