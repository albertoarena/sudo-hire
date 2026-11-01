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

it('passes a job when its main source or one of its "also on" sources is enabled', function (array $alsoOn, array $disabled, bool $passes) {
    $job = Make::job(['source' => 'remotive']);

    expect(SourceFilter::passes($job, Make::settings(['disabledSources' => $disabled]), $alsoOn))->toBe($passes);
})->with([
    'main source enabled, no "also on"' => [[], [], true],
    'main source disabled, "also on" enabled' => [['himalayas'], ['remotive'], true],
    'main source disabled, second "also on" enabled' => [['himalayas', 'jobicy'], ['remotive', 'himalayas'], true],
    'main source enabled, "also on" disabled' => [['himalayas'], ['himalayas'], true],
    'all sources disabled' => [['himalayas', 'jobicy'], ['remotive', 'himalayas', 'jobicy'], false],
]);
