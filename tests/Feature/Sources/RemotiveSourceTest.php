<?php

use App\Sources\JobSource;
use App\Sources\Remotive\RemotiveSource;
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

function remotiveItem(int $index = 0, array $changes = []): array
{
    return array_merge(JobFields::json('remotive/remote-jobs.json')['jobs'][$index], $changes);
}

it('is a job source with its key, attribution and interval', function () {
    $source = new RemotiveSource;

    expect($source)->toBeInstanceOf(JobSource::class)
        ->and($source->key())->toBe('remotive')
        ->and($source->attribution())->toBe('via Remotive')
        ->and($source->minInterval()->totalHours)->toEqual(6);
});

it('reads the PHP jobs with one request', function () {
    Http::fake(['remotive.com/*' => Http::response(JobFields::fixture('remotive/remote-jobs.json'))]);

    $items = iterator_to_array((new RemotiveSource)->fetch(), false);

    expect($items)->toHaveCount(3)
        ->and($items[0]['id'])->toBe(2069746);

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request) => $request->url() === 'https://remotive.com/api/remote-jobs?search=php');
});

it('normalises a job field by field', function () {
    $job = (new RemotiveSource)->normalise(remotiveItem(0));

    expect(JobFields::of($job))->toBe([
        'source' => 'remotive',
        'sourceJobId' => '2069746',
        'sourceUrl' => 'https://remotive.com/remote-jobs/software-development/tech-lead-full-stack-rails-engineer-2069746',
        'applyUrl' => 'https://remotive.com/remote-jobs/software-development/tech-lead-full-stack-rails-engineer-2069746',
        'title' => 'Tech Lead Full-Stack Rails Engineer',
        'company' => 'Mitre Media',
        'tags' => [
            'api', 'css', 'docker', 'elasticsearch', 'fullstack', 'html', 'javascript', 'kubernetes',
            'postgresql', 'react', 'ror', 'ruby/rails', 'ai/ml', 'cad', 'advertising', 'redis', 'es6',
            'web applications', 'mysql', 'nlp', 'fintech', 'micro services', 'github', 'system architecture',
        ],
        'workMode' => 'remote',
        'eligibility' => ['type' => 'restricted', 'regions' => [], 'countries' => ['US', 'CA']],
        'officeLocation' => null,
        'timezoneMin' => -10.0,
        'timezoneMax' => -4.0,
        'salary' => ['min' => 170000.0, 'max' => 200000.0, 'currency' => 'USD', 'period' => 'unknown', 'raw' => '$170k - $200k'],
        'contractType' => 'full_time',
        'seniority' => 'lead',
        'postedAt' => '2026-10-14T20:33:44Z',
        'fetchedAt' => '2026-10-29T10:00:00Z',
        'expiresAt' => null,
        'attribution' => 'via Remotive',
    ]);

    expect($job->description)
        ->toStartWith("About Mitre Media\n\nMitre Media is redefining FinTech")
        ->not->toContain('<p');
});

it('reads a publication date without a timezone as UTC', function () {
    $previous = date_default_timezone_get();
    date_default_timezone_set('Europe/Rome');

    try {
        $postedAt = (new RemotiveSource)->normalise(remotiveItem(0, ['publication_date' => '2026-07-01T12:00:00']))->postedAt;
    } finally {
        date_default_timezone_set($previous);
    }

    expect($postedAt->toIso8601ZuluString())->toBe('2026-07-01T12:00:00Z');
});

it('reads the location, the salary and the title of each job', function (string $fixture, int $index, array $fields) {
    $job = (new RemotiveSource)->normalise(JobFields::json($fixture)['jobs'][$index]);

    expect(JobFields::parsed($job))->toBe(JobFields::expected(['workMode' => 'remote'] + $fields));
})->with([
    'US and Canada with time zones' => ['remotive/remote-jobs.json', 0, [
        'eligibility' => JobFields::restricted(['US', 'CA']),
        'timezoneMin' => -10.0,
        'timezoneMax' => -4.0,
        'salary' => ['min' => 170000.0, 'max' => 200000.0, 'currency' => 'USD', 'period' => 'unknown', 'raw' => '$170k - $200k'],
        'contractType' => 'full_time',
        'seniority' => 'lead',
    ]],
    'worldwide' => ['remotive/remote-jobs.json', 1, [
        'eligibility' => ['type' => 'worldwide', 'regions' => [], 'countries' => []],
        'contractType' => 'full_time',
        'seniority' => 'lead',
    ]],
    'a region and countries' => ['remotive/remote-jobs.json', 2, [
        'eligibility' => JobFields::restricted(['US', 'GB', 'CA', 'AU', 'IE', 'CH', 'SG', 'MX', 'IS', 'NO'], ['europe']),
        'contractType' => 'full_time',
        'seniority' => 'senior',
    ]],
    // The job type says "full_time" and the title says "Contractor": the job type wins.
    'on-target earnings, the job type wins over the title' => ['remotive/remote-jobs-contract.json', 0, [
        'eligibility' => ['type' => 'worldwide', 'regions' => [], 'countries' => []],
        'salary' => ['min' => 25000.0, 'max' => 35000.0, 'currency' => 'USD', 'period' => 'unknown', 'raw' => 'OTE $25k - $35k'],
        'contractType' => 'full_time',
    ]],
    'hourly freelance' => ['remotive/remote-jobs-contract.json', 1, [
        'eligibility' => ['type' => 'worldwide', 'regions' => [], 'countries' => []],
        'salary' => ['min' => 50.0, 'max' => 75.0, 'currency' => 'USD', 'period' => 'hour', 'raw' => '$50-$75 /hour'],
        'contractType' => 'contract',
    ]],
]);

it('takes the contract type from the title when the job type is empty', function () {
    $item = array_merge(JobFields::json('remotive/remote-jobs-contract.json')['jobs'][0], ['job_type' => '']);

    expect((new RemotiveSource)->normalise($item)->contractType->value)->toBe('contract');
});

it('maps the job type to the contract type', function (string $type, string $contract) {
    expect((new RemotiveSource)->normalise(remotiveItem(0, ['job_type' => $type]))->contractType->value)->toBe($contract);
})->with([
    ['full_time', 'full_time'],
    ['part_time', 'part_time'],
    ['contract', 'contract'],
    ['freelance', 'contract'],
    ['internship', 'unknown'],
    ['', 'unknown'],
]);

it('fails when the API does not answer', function () {
    Http::fake(['remotive.com/*' => Http::response('', 503)]);

    iterator_to_array((new RemotiveSource)->fetch());
})->throws(RequestException::class);

it('fails when the response has no job list', function () {
    Http::fake(['remotive.com/*' => Http::response('{"job-count":0}')]);

    iterator_to_array((new RemotiveSource)->fetch());
})->throws(RuntimeException::class, 'The Remotive response has no job list.');

it('skips a job that cannot be normalised', function (mixed $item) {
    (new RemotiveSource)->normalise($item);
})->throws(SkipItem::class)->with([
    'not an array' => ['text'],
    'no title' => [fn () => remotiveItem(0, ['title' => ''])],
    'no company' => [fn () => remotiveItem(0, ['company_name' => null])],
    'no link' => [fn () => remotiveItem(0, ['url' => null])],
    'a link that is not http' => [fn () => remotiveItem(0, ['url' => 'remotive.com/jobs/1'])],
    'no id' => [fn () => remotiveItem(0, ['id' => null])],
    'no date' => [fn () => remotiveItem(0, ['publication_date' => null])],
    'a bad date' => [fn () => remotiveItem(0, ['publication_date' => 'yesterday at noon-ish'])],
]);

it('narrows a worldwide location to the country in the title', function () {
    $job = (new RemotiveSource)->normalise(remotiveItem(0, ['title' => 'Laravel Engineer (US only)', 'candidate_required_location' => 'Worldwide']));

    expect(JobFields::of($job)['eligibility'])->toBe(JobFields::restricted(['US']));
});
