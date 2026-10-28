<?php

use App\Schema\NormalisedJob;
use App\Sources\JobSource;
use App\Sources\SkipItem;

it('has a key made of lowercase letters, digits, dashes or underscores', function (JobSource $source) {
    expect($source->key())->toMatch('/^[a-z0-9_-]+$/');
})->with('job sources');

it('has an attribution', function (JobSource $source) {
    expect(trim($source->attribution()))->not->toBe('');
})->with('job sources');

it('has a positive minimum interval', function (JobSource $source) {
    expect($source->minInterval()->totalSeconds)->toBeGreaterThan(0);
})->with('job sources');

it('normalises every item or throws SkipItem', function (JobSource $source) {
    $items = 0;

    foreach ($source->fetch() as $raw) {
        $items++;

        try {
            $job = $source->normalise($raw);
        } catch (SkipItem) {
            continue;
        }

        expect($job)->toBeInstanceOf(NormalisedJob::class)
            ->and($job->source)->toBe($source->key())
            ->and($job->attribution)->toBe($source->attribution());
    }

    expect($items)->toBeGreaterThan(0);
})->with('job sources');
