<?php

use App\Filters\SourceFilter;
use Tests\Unit\Filters\Make;

it('fails a job from a disabled source', function (string $source, array $disabled, bool $passes) {
    $job = Make::job(['source' => $source]);

    expect(SourceFilter::passes($job, Make::settings(['disabledSources' => $disabled])))->toBe($passes);
})->with([
    'no source disabled' => ['remotive', [], true],
    'source disabled' => ['remotive', ['remotive'], false],
    'other source disabled' => ['remotive', ['himalayas', 'jobicy'], true],
]);
