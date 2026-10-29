<?php

use App\Parsers\Salary\UnknownSalaryShare;
use App\Schema\NormalisedJob;
use App\Schema\Salary;
use App\Schema\SalaryPeriod;

function jobWithSalary(?Salary $salary): NormalisedJob
{
    return new NormalisedJob(...jobAttributes(['salary' => $salary]));
}

function aSalary(): Salary
{
    return new Salary(170000, 200000, 'USD', SalaryPeriod::Unknown, '$170k - $200k');
}

it('gives zero for no jobs', function () {
    $share = UnknownSalaryShare::of([]);

    expect($share->unknown)->toBe(0)
        ->and($share->total)->toBe(0)
        ->and($share->share)->toBe(0.0);
});

it('gives one when no job has a salary', function () {
    $share = UnknownSalaryShare::of([jobWithSalary(null), jobWithSalary(null)]);

    expect($share->unknown)->toBe(2)
        ->and($share->total)->toBe(2)
        ->and($share->share)->toBe(1.0);
});

it('counts only the jobs with no salary in a mix', function () {
    $share = UnknownSalaryShare::of([
        jobWithSalary(null),
        jobWithSalary(aSalary()),
        jobWithSalary(aSalary()),
        jobWithSalary(aSalary()),
    ]);

    expect($share->unknown)->toBe(1)
        ->and($share->total)->toBe(4)
        ->and($share->share)->toBe(0.25);
});
