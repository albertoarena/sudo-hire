<?php

use App\Filters\WorkModeFilter;
use App\Schema\WorkMode;
use Tests\Unit\Filters\Make;

it('passes a job whose work mode is in the setting', function (WorkMode $mode, array $setting, bool $passes) {
    $job = Make::job(['workMode' => $mode]);

    expect(WorkModeFilter::passes($job, Make::settings(['workModes' => $setting])))->toBe($passes);
})->with([
    'remote, setting remote' => [WorkMode::Remote, ['remote'], true],
    'hybrid, setting remote' => [WorkMode::Hybrid, ['remote'], false],
    'onsite, setting remote and hybrid' => [WorkMode::Onsite, ['remote', 'hybrid'], false],
    'hybrid, setting remote and hybrid' => [WorkMode::Hybrid, ['remote', 'hybrid'], true],
    'unknown always passes' => [WorkMode::Unknown, ['onsite'], true],
]);
