<?php

use App\Filters\CompanyBlocklistFilter;
use Tests\Unit\Filters\Make;

it('fails a job from a blocked company, case-insensitive and trimmed', function (string $company, array $blocklist, bool $passes) {
    $job = Make::job(['company' => $company]);

    expect(CompanyBlocklistFilter::passes($job, Make::settings(['companyBlocklist' => $blocklist])))->toBe($passes);
})->with([
    'empty list' => ['Lemon.io', [], true],
    'same name' => ['Lemon.io', ['Lemon.io'], false],
    'other case' => ['Lemon.io', ['lemon.IO'], false],
    'spaces around the job company' => ['  Mitre Media ', ['mitre media'], false],
    'spaces around the setting' => ['Mitre Media', ['  Mitre Media  '], false],
    'other company' => ['Mitre Media', ['Lemon.io'], true],
    'part of the name only' => ['Mitre Media', ['Mitre'], true],
]);
