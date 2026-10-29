<?php

use App\Sources\JobSource;
use App\Sources\LaraJobs\LaraJobsSource;
use App\Sources\SkipItem;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\Support\JobFields;

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-10-29T10:00:00Z');
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

/**
 * @return list<array<string, mixed>>
 */
function laraJobsItems(): array
{
    Http::fake(['larajobs.com/*' => Http::response(JobFields::fixture('larajobs/feed.xml'), 200, ['Content-Type' => 'application/rss+xml'])]);

    return iterator_to_array((new LaraJobsSource)->fetch(), false);
}

it('is a job source with its key, attribution and interval', function () {
    $source = new LaraJobsSource;

    expect($source)->toBeInstanceOf(JobSource::class)
        ->and($source->key())->toBe('larajobs')
        ->and($source->attribution())->toBe('via LaraJobs')
        ->and($source->minInterval()->totalHours)->toEqual(2);
});

it('reads the items of the RSS feed', function () {
    expect(laraJobsItems())->toHaveCount(3);

    Http::assertSent(fn (Request $request) => $request->url() === 'https://larajobs.com/feed');
});

it('normalises an item field by field', function () {
    $job = (new LaraJobsSource)->normalise(laraJobsItems()[0]);

    expect(JobFields::of($job))->toBe([
        'source' => 'larajobs',
        'sourceJobId' => 'https://larajobs.com/job/3949',
        'sourceUrl' => 'https://larajobs.com/job/3949',
        'applyUrl' => 'https://larajobs.com/job/3949',
        'title' => 'Senior Full Stack Software Engineer (PHP/Laravel) - US Only',
        'company' => 'Evolution Collect, LLC',
        'tags' => ['full time', 'fullstack', 'laravel', 'mysql', 'vuejs'],
        'workMode' => 'unknown',
        'eligibility' => ['type' => 'unknown', 'regions' => [], 'countries' => []],
        'officeLocation' => null,
        'timezoneMin' => null,
        'timezoneMax' => null,
        'salary' => null,
        'contractType' => 'full_time',
        'seniority' => 'unknown',
        'postedAt' => '2026-10-25T18:21:28Z',
        'fetchedAt' => '2026-10-29T10:00:00Z',
        'expiresAt' => null,
        'attribution' => 'via LaraJobs',
    ]);

    expect($job->description)->toBeNull();
});

it('gives no tags for an empty tag list', function () {
    expect((new LaraJobsSource)->normalise(laraJobsItems()[2])->tags)->toBe([]);
});

it('maps the job type to the contract type', function (string $type, string $contract) {
    $item = array_merge(laraJobsItems()[0], ['job_type' => $type]);

    expect((new LaraJobsSource)->normalise($item)->contractType->value)->toBe($contract);
})->with([
    ['FULL_TIME', 'full_time'],
    ['PART_TIME', 'part_time'],
    ['CONTRACT', 'contract'],
    ['', 'unknown'],
    ['FREELANCE', 'unknown'],
]);

it('fails when the feed does not answer', function () {
    Http::fake(['larajobs.com/*' => Http::response('Server error', 500)]);

    iterator_to_array((new LaraJobsSource)->fetch());
})->throws(RequestException::class);

it('fails when the feed is not RSS', function () {
    Http::fake(['larajobs.com/*' => Http::response('<!DOCTYPE html><html><body>Maintenance</body></html>')]);

    iterator_to_array((new LaraJobsSource)->fetch());
})->throws(RuntimeException::class, 'The LaraJobs feed is not RSS.');

it('skips an item that cannot be normalised', function (mixed $item) {
    (new LaraJobsSource)->normalise($item);
})->throws(SkipItem::class)->with([
    'not an array' => ['text'],
    'no title' => [['guid' => 'x', 'link' => 'https://larajobs.com/job/1', 'title' => '', 'company' => 'Acme', 'pubDate' => 'Sun, 25 Oct 2026 18:21:28 +0000']],
    'no link' => [['guid' => 'x', 'link' => '', 'title' => 'Dev', 'company' => 'Acme', 'pubDate' => 'Sun, 25 Oct 2026 18:21:28 +0000']],
    'no company' => [['guid' => 'x', 'link' => 'https://larajobs.com/job/1', 'title' => 'Dev', 'company' => '', 'creator' => '', 'pubDate' => 'Sun, 25 Oct 2026 18:21:28 +0000']],
    'bad date' => [['guid' => 'x', 'link' => 'https://larajobs.com/job/1', 'title' => 'Dev', 'company' => 'Acme', 'pubDate' => 'yesterday-ish']],
]);
