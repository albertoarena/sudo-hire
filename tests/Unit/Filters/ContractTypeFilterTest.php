<?php

use App\Filters\ContractTypeFilter;
use App\Schema\ContractType;
use Tests\Unit\Filters\Make;

it('passes a job whose contract type is in the setting', function (ContractType $type, array $setting, bool $passes) {
    $job = Make::job(['contractType' => $type]);

    expect(ContractTypeFilter::passes($job, Make::settings(['contractTypes' => $setting])))->toBe($passes);
})->with([
    'full time, default setting' => [ContractType::FullTime, ['full_time', 'part_time', 'contract'], true],
    'contract, setting full time' => [ContractType::Contract, ['full_time'], false],
    'part time, setting part time and contract' => [ContractType::PartTime, ['part_time', 'contract'], true],
    'unknown always passes' => [ContractType::Unknown, ['contract'], true],
]);
