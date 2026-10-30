<?php

use App\Currency\CurrencyConverter;
use App\Filters\SalaryFilter;
use App\Schema\ContractType;
use App\Schema\Salary;
use App\Schema\SalaryPeriod;
use Tests\Unit\Filters\Make;

/** 1 EUR is 1.25 USD and 0.8 GBP. JPY has no rate. */
function salaryConverter(): CurrencyConverter
{
    return new CurrencyConverter(['USD' => 1.25, 'GBP' => 0.8]);
}

function pay(?float $min, ?float $max, string $currency, SalaryPeriod $period, string $raw = 'salary'): Salary
{
    return new Salary($min, $max, $currency, $period, $raw);
}

/**
 * @param  array<string, mixed>  $settings
 * @param  array{bool, bool, bool}  $expected  passes, unknown ("?" badge), OTE
 */
function expectSalaryCheck(ContractType $type, ?Salary $salary, array $settings, array $expected): void
{
    $check = SalaryFilter::check(Make::job(['contractType' => $type, 'salary' => $salary]), Make::settings($settings), salaryConverter());

    expect([$check->passes, $check->unknown, $check->ote])->toBe($expected);
}

$annual = ['minAnnualSalary' => 50000];
$dayRate = ['minDayRate' => 400];
$pass = [true, false, false];
$fail = [false, false, false];
$lenient = [true, true, false];

it('compares the annual salary of employee jobs', function (ContractType $type, Salary $salary, array $settings, array $expected) {
    expectSalaryCheck($type, $salary, $settings, $expected);
})->with([
    'year, above' => [ContractType::FullTime, pay(60000, null, 'EUR', SalaryPeriod::Year), $annual, $pass],
    'year, exactly the minimum' => [ContractType::FullTime, pay(50000, null, 'EUR', SalaryPeriod::Year), $annual, $pass],
    'year, below' => [ContractType::FullTime, pay(40000, null, 'EUR', SalaryPeriod::Year), $annual, $fail],
    'part time uses the annual minimum' => [ContractType::PartTime, pay(40000, null, 'EUR', SalaryPeriod::Year), $annual, $fail],
    'month, 12 payments' => [ContractType::FullTime, pay(4000, null, 'EUR', SalaryPeriod::Month), [...$annual, 'monthlyPayments' => 12], $fail],
    'month, 13 payments' => [ContractType::FullTime, pay(4000, null, 'EUR', SalaryPeriod::Month), [...$annual, 'monthlyPayments' => 13], $pass],
    'month, 14 payments' => [ContractType::FullTime, pay(4000, null, 'EUR', SalaryPeriod::Month), [...$annual, 'monthlyPayments' => 14], $pass],
    'Himalayas monthly gross' => [ContractType::FullTime, pay(5000, 5800, 'EUR', SalaryPeriod::Month, 'Salary range: €5000 - €5800 (GROSS) / month.'), ['minAnnualSalary' => 69600], $pass],
    'range, compares the max' => [ContractType::FullTime, pay(40000, 60000, 'EUR', SalaryPeriod::Year), $annual, $pass],
    'range with only a min' => [ContractType::FullTime, pay(60000, null, 'EUR', SalaryPeriod::Year), ['minAnnualSalary' => 60001], $fail],
]);

it('reads an amount with no period as yearly from 10,000 in the original currency', function (Salary $salary, array $settings, array $expected) {
    expectSalaryCheck(ContractType::FullTime, $salary, $settings, $expected);
})->with([
    'LaraJobs pounds, above' => [pay(70000, 90000, 'GBP', SalaryPeriod::Unknown, '£70,000 - £90,000'), ['minAnnualSalary' => 112500], $pass],
    'LaraJobs pounds, below' => [pay(70000, 90000, 'GBP', SalaryPeriod::Unknown, '£70,000 - £90,000'), ['minAnnualSalary' => 112501], $fail],
    'exactly 10,000' => [pay(10000, null, 'EUR', SalaryPeriod::Unknown), ['minAnnualSalary' => 9000], $pass],
    'below 10,000 is not comparable' => [pay(9999, null, 'EUR', SalaryPeriod::Unknown), ['minAnnualSalary' => 9000], $lenient],
    '10,000 GBP counts as yearly, compared in EUR' => [pay(10000, null, 'GBP', SalaryPeriod::Unknown), ['minAnnualSalary' => 12500], $pass],
    'below 10,000, strict' => [pay(9999, null, 'EUR', SalaryPeriod::Unknown), ['minAnnualSalary' => 9000, 'unknownSalary' => 'strict'], $fail],
]);

it('compares the day rate of contract jobs', function (ContractType $type, Salary $salary, array $settings, array $expected) {
    expectSalaryCheck($type, $salary, $settings, $expected);
})->with([
    'day, above' => [ContractType::Contract, pay(450, null, 'EUR', SalaryPeriod::Day), $dayRate, $pass],
    'day, below' => [ContractType::Contract, pay(350, null, 'EUR', SalaryPeriod::Day), $dayRate, $fail],
    'hour, 8 hours' => [ContractType::Contract, pay(60, null, 'EUR', SalaryPeriod::Hour), $dayRate, $pass],
    'hour, 6 hours' => [ContractType::Contract, pay(60, null, 'EUR', SalaryPeriod::Hour), [...$dayRate, 'hoursPerDay' => 6], $fail],
    'unknown contract type with an hourly rate is a contract' => [ContractType::Unknown, pay(50, 75, 'USD', SalaryPeriod::Hour, '$50-$75 /hour'), ['minDayRate' => 480], $pass],
    'unknown contract type with a daily rate is a contract' => [ContractType::Unknown, pay(350, null, 'EUR', SalaryPeriod::Day), $dayRate, $fail],
    'unknown contract type with an hourly rate ignores the annual minimum' => [ContractType::Unknown, pay(50, null, 'USD', SalaryPeriod::Hour), $annual, $pass],
    'unknown contract type with a yearly amount uses the annual minimum' => [ContractType::Unknown, pay(40000, null, 'EUR', SalaryPeriod::Year), $annual, $fail],
]);

it('converts to the display currency before it compares', function (Salary $salary, string $display, float $minimum, bool $passes) {
    expectSalaryCheck(ContractType::FullTime, $salary, ['minAnnualSalary' => $minimum, 'displayCurrency' => $display], [$passes, false, false]);
})->with([
    'USD to EUR, reaches' => [pay(100000, null, 'USD', SalaryPeriod::Year), 'EUR', 80000, true],
    'USD to EUR, does not reach' => [pay(100000, null, 'USD', SalaryPeriod::Year), 'EUR', 80001, false],
    'EUR to USD' => [pay(80000, null, 'EUR', SalaryPeriod::Year), 'USD', 100000, true],
    'USD to GBP' => [pay(100000, null, 'USD', SalaryPeriod::Year), 'GBP', 64001, false],
    'GBP to GBP' => [pay(64000, null, 'GBP', SalaryPeriod::Year), 'GBP', 64000, true],
]);

it('follows the unknown salary setting when a salary is missing or not comparable', function (ContractType $type, ?Salary $salary, array $settings) {
    expectSalaryCheck($type, $salary, [...$settings, 'unknownSalary' => 'lenient'], [true, true, false]);
    expectSalaryCheck($type, $salary, [...$settings, 'unknownSalary' => 'strict'], [false, false, false]);
})->with([
    'no salary' => [ContractType::FullTime, null, $annual],
    'no salary, contract' => [ContractType::Contract, null, $dayRate],
    'currency with no rate' => [ContractType::FullTime, pay(9000000, null, 'JPY', SalaryPeriod::Year), $annual],
    'hourly amount on an employee job' => [ContractType::FullTime, pay(50, 75, 'USD', SalaryPeriod::Hour, '$50-$75 /hour'), $annual],
    'daily amount on an employee job' => [ContractType::FullTime, pay(450, null, 'GBP', SalaryPeriod::Day), $annual],
    'yearly amount on a contract job' => [ContractType::Contract, pay(100000, null, 'EUR', SalaryPeriod::Year), $dayRate],
    'monthly amount on a contract job' => [ContractType::Contract, pay(8000, null, 'EUR', SalaryPeriod::Month), $dayRate],
    'amount with no period on a contract job' => [ContractType::Contract, pay(170000, 200000, 'USD', SalaryPeriod::Unknown, '$170k - $200k'), $dayRate],
]);

it('is off when the minimum for the job is null', function (ContractType $type, ?Salary $salary, array $settings) {
    expectSalaryCheck($type, $salary, [...$settings, 'unknownSalary' => 'strict'], [true, false, false]);
})->with([
    'employee job, only a day rate is set' => [ContractType::FullTime, null, $dayRate],
    'contract job, only an annual minimum is set' => [ContractType::Contract, pay(100, null, 'EUR', SalaryPeriod::Day), $annual],
    'nothing set' => [ContractType::FullTime, pay(1, null, 'EUR', SalaryPeriod::Year), []],
]);

it('uses an OTE amount like a salary and sets the OTE flag', function (array $settings, array $expected) {
    $salary = pay(25000, 35000, 'USD', SalaryPeriod::Unknown, 'OTE $25k - $35k');

    expectSalaryCheck(ContractType::FullTime, $salary, $settings, $expected);
})->with([
    'reaches the minimum' => [['minAnnualSalary' => 28000], [true, false, true]],
    'does not reach the minimum' => [['minAnnualSalary' => 28001], [false, false, true]],
    'filter off' => [[], [true, false, true]],
]);
