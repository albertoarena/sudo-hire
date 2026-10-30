<?php

use App\Currency\SalaryValues;
use App\Schema\Salary;
use App\Schema\SalaryPeriod;

it('gives the annual value of a monthly amount', function (int $payments, float $expected) {
    expect(SalaryValues::annual(5000, $payments))->toBe($expected);
})->with([
    '12 payments' => [12, 60000.0],
    '13 payments' => [13, 65000.0],
    '14 payments' => [14, 70000.0],
]);

it('refuses a number of payments other than 12, 13 or 14', function () {
    SalaryValues::annual(5000, 15);
})->throws(InvalidArgumentException::class);

it('gives the day rate of an hourly amount, 8 hours by default', function () {
    expect(SalaryValues::dayRate(60))->toBe(480.0)
        ->and(SalaryValues::dayRate(60, 7.5))->toBe(450.0);
});

it('compares a range by its max', function () {
    expect(SalaryValues::compared(new Salary(50, 75, 'USD', SalaryPeriod::Hour, '$50-$75 /hour')))->toBe(75.0);
});

it('compares a range with only a min by its min', function () {
    expect(SalaryValues::compared(new Salary(450, null, 'GBP', SalaryPeriod::Day, '£450 p/d')))->toBe(450.0);
});
