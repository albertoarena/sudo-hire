<?php

use App\Filters\SeniorityFilter;
use App\Schema\Seniority;
use Tests\Unit\Filters\Make;

it('passes a job whose seniority is in the setting', function (Seniority $seniority, array $setting, bool $passes) {
    $job = Make::job(['seniority' => $seniority]);

    expect(SeniorityFilter::passes($job, Make::settings(['seniorities' => $setting])))->toBe($passes);
})->with([
    'senior, setting senior and lead' => [Seniority::Senior, ['senior', 'lead'], true],
    'junior, setting senior and lead' => [Seniority::Junior, ['senior', 'lead'], false],
    'mid, setting mid' => [Seniority::Mid, ['mid'], true],
    'unknown always passes' => [Seniority::Unknown, ['lead'], true],
]);
