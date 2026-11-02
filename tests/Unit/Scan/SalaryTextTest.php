<?php

use App\Currency\CurrencyConverter;
use App\Currency\DisplayCurrency;
use App\Filters\FilterResult;
use App\Scan\SalaryText;
use App\Schema\Salary;
use App\Schema\SalaryPeriod;

function salaryText(?Salary $salary, bool $unknown = false, bool $ote = false, DisplayCurrency $currency = DisplayCurrency::Eur): string
{
    return SalaryText::of($salary, new FilterResult([], false, $unknown, $ote), new CurrencyConverter(['USD' => 1.25, 'GBP' => 0.8]), $currency);
}

it('shows the original salary and the converted salary', function (Salary $salary, string $text) {
    expect(salaryText($salary))->toBe($text);
})->with([
    'one amount a year' => [new Salary(100000, null, 'USD', SalaryPeriod::Year, '$100k'), '$100k (EUR 80,000 a year)'],
    'range a month' => [new Salary(5000, 6250, 'USD', SalaryPeriod::Month, '$5,000 - $6,250 per month'), '$5,000 - $6,250 per month (EUR 4,000-5,000 a month)'],
    'only a max' => [new Salary(null, 500, 'GBP', SalaryPeriod::Day, 'up to £500/day'), 'up to £500/day (EUR 625 a day)'],
    'an hour' => [new Salary(50, null, 'USD', SalaryPeriod::Hour, '$50/h'), '$50/h (EUR 40 an hour)'],
    'no period' => [new Salary(100000, null, 'USD', SalaryPeriod::Unknown, '$100k'), '$100k (EUR 80,000)'],
    'same currency' => [new Salary(60000, 70000, 'EUR', SalaryPeriod::Year, '€60k-70k'), '€60k-70k'],
    'no rate for the currency' => [new Salary(60000, null, 'CHF', SalaryPeriod::Year, 'CHF 60k'), 'CHF 60k'],
]);

it('converts to the display currency', function () {
    expect(salaryText(new Salary(100000, null, 'USD', SalaryPeriod::Year, '$100k'), currency: DisplayCurrency::Gbp))->toBe('$100k (GBP 64,000 a year)');
});

it('shows the "?" and "OTE" flags', function () {
    expect(salaryText(null))->toBe('not stated')
        ->and(salaryText(null, unknown: true))->toBe('not stated [?]')
        ->and(salaryText(new Salary(90000, null, 'EUR', SalaryPeriod::Year, '€90k OTE'), ote: true))->toBe('€90k OTE [OTE]')
        ->and(salaryText(new Salary(50, null, 'EUR', SalaryPeriod::Hour, '€50/h'), unknown: true))->toBe('€50/h [?]');
});

it('gives the converted salary alone, with "≈", for the list', function (?Salary $salary, ?string $text) {
    expect(SalaryText::converted($salary, new CurrencyConverter(['USD' => 1.25, 'GBP' => 0.8]), DisplayCurrency::Eur))->toBe($text);
})->with([
    'a range a month' => [new Salary(5000, 6250, 'USD', SalaryPeriod::Month, '$5,000 - $6,250 per month'), '≈ EUR 4,000-5,000 a month'],
    'no period' => [new Salary(100000, null, 'USD', SalaryPeriod::Unknown, '$100k'), '≈ EUR 80,000'],
    'same currency' => [new Salary(60000, 70000, 'EUR', SalaryPeriod::Year, '€60k-70k'), null],
    'no rate for the currency' => [new Salary(60000, null, 'CHF', SalaryPeriod::Year, 'CHF 60k'), null],
    'no salary' => [null, null],
]);
