<?php

use App\Schema\ContractType;
use App\Schema\EligibilityType;
use App\Schema\SalaryPeriod;
use App\Schema\Seniority;
use App\Schema\WorkMode;

it('has the values of the job schema', function (string $enum, array $values) {
    expect(array_column($enum::cases(), 'value'))->toBe($values);
})->with([
    'work mode' => [WorkMode::class, ['remote', 'hybrid', 'onsite', 'unknown']],
    'contract type' => [ContractType::class, ['full_time', 'part_time', 'contract', 'unknown']],
    'seniority' => [Seniority::class, ['junior', 'mid', 'senior', 'lead', 'unknown']],
    'eligibility type' => [EligibilityType::class, ['worldwide', 'restricted', 'unknown']],
    'salary period' => [SalaryPeriod::class, ['year', 'month', 'day', 'hour', 'unknown']],
]);
