<?php

use App\Currency\CurrencyConverter;
use App\Filters\FailReason;
use App\Filters\FilterResult;
use App\Filters\JobFilter;
use App\Schema\ContractType;
use App\Schema\Eligibility;
use App\Schema\Salary;
use App\Schema\SalaryPeriod;
use App\Schema\Seniority;
use App\Schema\WorkMode;
use Carbon\CarbonImmutable;
use Tests\Unit\Filters\Make;

function evaluateJob(array $job, array $settings = [], string $now = '2026-10-28T09:00:00Z'): FilterResult
{
    return JobFilter::evaluate(
        Make::job([...['eligibility' => Eligibility::worldwide()], ...$job]),
        Make::settings(['country' => 'IT', ...$settings]),
        new CurrencyConverter(['USD' => 1.25]),
        CarbonImmutable::parse($now),
    );
}

it('passes a job that passes every filter, with no reasons and no flags', function () {
    $result = evaluateJob([]);

    expect($result->passes)->toBeTrue()
        ->and($result->reasons)->toBe([])
        ->and($result->locationNotStated)->toBeFalse()
        ->and($result->salaryUnknown)->toBeFalse()
        ->and($result->ote)->toBeFalse();
});

it('gives one reason for each failing filter', function () {
    $result = evaluateJob(['workMode' => WorkMode::Hybrid, 'company' => 'Acme'], ['companyBlocklist' => ['acme']]);

    expect($result->passes)->toBeFalse()
        ->and($result->reasons)->toBe([FailReason::WorkMode, FailReason::Company]);
});

it('gives the reason of each filter', function (array $job, array $settings, FailReason $reason, string $now = '2026-10-28T09:00:00Z') {
    expect(evaluateJob($job, $settings, $now)->reasons)->toBe([$reason]);
})->with([
    'work mode' => [['workMode' => WorkMode::Onsite], [], FailReason::WorkMode],
    'eligibility' => [['eligibility' => Eligibility::restricted([], ['US'])], [], FailReason::Eligibility],
    'timezone' => [['timezoneMin' => -8.0, 'timezoneMax' => -5.0], ['timezoneOverlapHours' => 2], FailReason::Timezone],
    'include keyword' => [[], ['keywordsInclude' => ['django']], FailReason::KeywordMissing],
    'exclude keyword' => [[], ['keywordsExclude' => ['laravel']], FailReason::KeywordExcluded],
    'salary' => [['salary' => new Salary(40000, null, 'EUR', SalaryPeriod::Year, '€40k')], ['minAnnualSalary' => 50000], FailReason::Salary],
    'contract type' => [['contractType' => ContractType::Contract], ['contractTypes' => ['full_time']], FailReason::ContractType],
    'seniority' => [[], ['seniorities' => ['junior']], FailReason::Seniority],
    'posted within' => [[], ['postedWithinHours' => 24], FailReason::PostedWithin, '2026-10-29T09:00:01Z'],
    'company' => [[], ['companyBlocklist' => ['Acme']], FailReason::Company],
    'source' => [[], ['disabledSources' => ['fake']], FailReason::Source],
    'expired' => [['expiresAt' => CarbonImmutable::parse('2026-10-27T12:00:00Z')], [], FailReason::Expired],
]);

it('puts a job with unknown eligibility in the "Location not stated" group in group mode', function (string $mode, bool $passes, bool $grouped) {
    $result = evaluateJob(['eligibility' => Eligibility::unknown()], ['unknownEligibility' => $mode]);

    expect($result->passes)->toBe($passes)
        ->and($result->locationNotStated)->toBe($grouped);
})->with([
    'group' => ['group', true, true],
    'strict' => ['strict', false, false],
    'lenient' => ['lenient', true, false],
]);

it('sets the "?" flag for a missing salary in lenient mode', function () {
    $result = evaluateJob([], ['minAnnualSalary' => 50000]);

    expect($result->passes)->toBeTrue()
        ->and($result->salaryUnknown)->toBeTrue();
});

it('sets the OTE flag', function () {
    $result = evaluateJob(['salary' => new Salary(25000, 35000, 'USD', SalaryPeriod::Unknown, 'OTE $25k - $35k')]);

    expect($result->passes)->toBeTrue()
        ->and($result->ote)->toBeTrue();
});

it('lets unknown work mode, contract type and seniority pass a subset setting', function () {
    $result = evaluateJob(
        ['workMode' => WorkMode::Unknown, 'contractType' => ContractType::Unknown, 'seniority' => Seniority::Unknown],
        ['workModes' => ['onsite'], 'contractTypes' => ['contract'], 'seniorities' => ['lead']],
    );

    expect($result->passes)->toBeTrue();
});

it('passes the source filter with an enabled "also on" source', function (array $alsoOn, bool $passes) {
    $result = JobFilter::evaluate(
        Make::job(['eligibility' => Eligibility::worldwide()]),
        Make::settings(['country' => 'IT', 'disabledSources' => ['fake', 'remotive']]),
        new CurrencyConverter([]),
        CarbonImmutable::parse('2026-10-28T09:00:00Z'),
        $alsoOn,
    );

    expect($result->passes)->toBe($passes);
})->with([
    'no "also on" source' => [[], false],
    'one "also on" source enabled' => [['remotive', 'himalayas'], true],
    'every "also on" source disabled' => [['remotive'], false],
]);
