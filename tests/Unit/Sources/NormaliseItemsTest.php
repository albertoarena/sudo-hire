<?php

use App\Sources\NormaliseItems;
use Carbon\CarbonImmutable;
use Tests\Fakes\FakeSource;

it('keeps going after an item throws SkipItem and counts it', function () {
    $batch = (new NormaliseItems)(new FakeSource);

    expect(array_map(fn ($job) => $job->sourceJobId, $batch->jobs))->toBe(['1', '3'])
        ->and($batch->skipped)->toBe(1)
        ->and($batch->skipReasons)->toBe(['item has no title']);
});

it('passes $since to the source', function () {
    $source = new FakeSource;
    $since = CarbonImmutable::parse('2026-10-27T00:00:00Z');

    $batch = (new NormaliseItems)($source, $since);

    expect($source->fetchedSince)->toBe([$since])
        ->and(array_map(fn ($job) => $job->sourceJobId, $batch->jobs))->toBe(['1'])
        ->and($batch->skipped)->toBe(1);
});
