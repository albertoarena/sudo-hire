<?php

use App\Schema\InvalidJob;
use App\Schema\Salary;
use App\Schema\SalaryPeriod;

it('builds from valid data', function () {
    $salary = Salary::fromArray([
        'min' => 108000,
        'max' => 145000,
        'currency' => 'EUR',
        'period' => 'year',
        'raw' => '€108K - €145K',
    ]);

    expect($salary->min)->toBe(108000.0)
        ->and($salary->max)->toBe(145000.0)
        ->and($salary->currency)->toBe('EUR')
        ->and($salary->period)->toBe(SalaryPeriod::Year)
        ->and($salary->raw)->toBe('€108K - €145K');
});

it('accepts only a min or only a max', function () {
    expect(Salary::fromArray(['min' => 450, 'currency' => 'GBP', 'period' => 'day', 'raw' => '£450 p/d'])->max)->toBeNull()
        ->and(Salary::fromArray(['max' => 60, 'currency' => 'USD', 'period' => 'hour', 'raw' => 'up to $60/hour'])->min)->toBeNull();
});

it('rejects an invalid period', function () {
    Salary::fromArray(['min' => 1, 'currency' => 'EUR', 'period' => 'yearly', 'raw' => '']);
})->throws(InvalidJob::class);

it('rejects a currency that is not ISO 4217', function (string $currency) {
    Salary::fromArray(['min' => 1, 'currency' => $currency, 'period' => 'year', 'raw' => '']);
})->throws(InvalidJob::class)->with(['eur', '€', 'EURO', '']);

it('rejects a salary without min and max', function () {
    Salary::fromArray(['currency' => 'EUR', 'period' => 'year', 'raw' => 'competitive']);
})->throws(InvalidJob::class);

it('rejects a negative amount', function () {
    Salary::fromArray(['min' => -1, 'currency' => 'EUR', 'period' => 'year', 'raw' => '']);
})->throws(InvalidJob::class);

it('rejects min above max', function () {
    Salary::fromArray(['min' => 70000, 'max' => 60000, 'currency' => 'EUR', 'period' => 'year', 'raw' => '']);
})->throws(InvalidJob::class);
