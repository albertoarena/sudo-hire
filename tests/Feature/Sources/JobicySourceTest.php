<?php

use App\Sources\Jobicy\JobicySource;
use App\Sources\JobSource;
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

function jobicyItem(int $index = 0, array $changes = []): array
{
    return array_merge(JobFields::json('jobicy/remote-jobs.json')['jobs'][$index], $changes);
}

it('is a job source with its key, attribution and interval', function () {
    $source = new JobicySource;

    expect($source)->toBeInstanceOf(JobSource::class)
        ->and($source->key())->toBe('jobicy')
        ->and($source->attribution())->toBe('via Jobicy')
        ->and($source->minInterval()->totalHours)->toEqual(6);
});

it('reads the newest 100 engineering jobs from the API', function () {
    Http::fake(['jobicy.com/*' => Http::response(JobFields::fixture('jobicy/jobs.json'))]);

    $items = iterator_to_array((new JobicySource)->fetch(), false);

    expect($items)->toHaveCount(3)
        ->and($items[0]['id'])->toBe(152299);

    Http::assertSent(fn (Request $request) => $request->url() === 'https://jobicy.com/api/jobs?count=100&industry=engineering');
});

it('reads a job of the jobs endpoint', function () {
    $job = (new JobicySource)->normalise(JobFields::json('jobicy/jobs.json')['jobs'][1]);

    expect($job->sourceJobId)->toBe('158323')
        ->and($job->seniority->value)->toBe('junior')
        ->and($job->eligibility->regions)->toBe(['europe'])
        ->and($job->salary)->toBeNull();
});

it('normalises a job field by field', function () {
    $job = (new JobicySource)->normalise(jobicyItem(0));

    expect(JobFields::of($job))->toBe([
        'source' => 'jobicy',
        'sourceJobId' => '157071',
        'sourceUrl' => 'https://jobicy.com/jobs/157071-security-operations-analyst-west-coast-shift',
        'applyUrl' => 'https://jobicy.com/jobs/157071-security-operations-analyst-west-coast-shift',
        'title' => 'Security Operations Analyst - West Coast Shift',
        'company' => 'Huntress',
        'tags' => ['cybersecurity'],
        'workMode' => 'remote',
        'eligibility' => ['type' => 'restricted', 'regions' => [], 'countries' => ['US']],
        'officeLocation' => null,
        'timezoneMin' => null,
        'timezoneMax' => null,
        'salary' => ['min' => 100000.0, 'max' => 125000.0, 'currency' => 'USD', 'period' => 'year', 'raw' => '100000 - 125000 USD yearly'],
        'contractType' => 'full_time',
        'seniority' => 'unknown',
        'postedAt' => '2026-10-26T06:09:00Z',
        'fetchedAt' => '2026-10-29T10:00:00Z',
        'expiresAt' => null,
        'attribution' => 'via Jobicy',
    ]);

    expect($job->description)
        ->toStartWith("What We Do:\n\nCybercrime is growing")
        ->not->toContain('<p>');
});

it('reads the location and the title of each job', function (string $fixture, int $index, array $fields) {
    $job = (new JobicySource)->normalise(JobFields::json($fixture)['jobs'][$index]);

    expect(JobFields::parsed($job))->toBe(JobFields::expected(['workMode' => 'remote', 'contractType' => 'full_time'] + $fields));
})->with([
    'USA, any level, no level in the title' => ['jobicy/remote-jobs.json', 0, [
        'eligibility' => JobFields::restricted(['US']),
        'salary' => ['min' => 100000.0, 'max' => 125000.0, 'currency' => 'USD', 'period' => 'year', 'raw' => '100000 - 125000 USD yearly'],
    ]],
    'Europe, director' => ['jobicy/remote-jobs.json', 1, [
        'eligibility' => JobFields::restricted([], ['europe']),
        'seniority' => 'lead',
    ]],
    'Philippines, any level' => ['jobicy/remote-jobs.json', 2, [
        'eligibility' => JobFields::restricted(['PH']),
    ]],
    // The job level says "Senior" and the title says "Junior": the job level wins.
    'Philippines, the job level wins over the title' => ['jobicy/remote-jobs-junior.json', 0, [
        'eligibility' => JobFields::restricted(['PH']),
        'seniority' => 'senior',
    ]],
]);

it('takes the seniority from the title when the job level is "Any"', function () {
    $item = array_merge(JobFields::json('jobicy/remote-jobs-junior.json')['jobs'][0], ['jobLevel' => 'Any']);

    $job = (new JobicySource)->normalise($item);

    expect($job->seniority->value)->toBe('junior');
});

it('takes the contract type from the title when the job type is empty', function () {
    expect((new JobicySource)->normalise(jobicyItem(0, ['jobType' => [], 'jobTitle' => 'Freelance Writer']))->contractType->value)->toBe('contract');
});

it('gives no salary when the job has no salary fields', function () {
    expect((new JobicySource)->normalise(jobicyItem(1))->salary)->toBeNull();
});

it('maps the salary period', function (string $period, string $expected) {
    expect((new JobicySource)->normalise(jobicyItem(0, ['salaryPeriod' => $period]))->salary?->period->value)->toBe($expected);
})->with([
    ['yearly', 'year'],
    ['monthly', 'month'],
    ['daily', 'day'],
    ['hourly', 'hour'],
    ['weekly', 'unknown'],
]);

it('maps the job level to the seniority', function (string $level, string $seniority) {
    expect((new JobicySource)->normalise(jobicyItem(0, ['jobLevel' => $level]))->seniority->value)->toBe($seniority);
})->with([
    ['Junior', 'junior'],
    ['Entry-Level, Junior', 'junior'],
    ['Midweight', 'mid'],
    ['Senior', 'senior'],
    ['Manager', 'lead'],
    ['Director', 'lead'],
    ['Any', 'unknown'],
]);

it('maps the job type to the contract type', function (array $types, string $contract) {
    expect((new JobicySource)->normalise(jobicyItem(0, ['jobType' => $types]))->contractType->value)->toBe($contract);
})->with([
    [['Full-Time'], 'full_time'],
    [['Part-Time'], 'part_time'],
    [['Contract'], 'contract'],
    [['Internship'], 'unknown'],
    [[], 'unknown'],
]);

it('fails when the API does not answer', function () {
    Http::fake(['jobicy.com/*' => Http::response('', 503)]);

    iterator_to_array((new JobicySource)->fetch());
})->throws(RequestException::class);

it('fails when the response has no job list', function () {
    Http::fake(['jobicy.com/*' => Http::response('{"success":false}')]);

    iterator_to_array((new JobicySource)->fetch());
})->throws(RuntimeException::class, 'The Jobicy response has no job list.');

it('skips a job that cannot be normalised', function (mixed $item) {
    (new JobicySource)->normalise($item);
})->throws(SkipItem::class)->with([
    'not an array' => ['text'],
    'no title' => [fn () => jobicyItem(0, ['jobTitle' => ''])],
    'no link' => [fn () => jobicyItem(0, ['url' => null])],
    'no id' => [fn () => jobicyItem(0, ['id' => null])],
    'no date' => [fn () => jobicyItem(0, ['pubDate' => null])],
]);

it('gives no salary for a currency that is not ISO 4217', function () {
    expect((new JobicySource)->normalise(jobicyItem(0, ['salaryCurrency' => 'dollars']))->salary)->toBeNull();
});

it('keeps the places of the location that the title also names', function () {
    $job = (new JobicySource)->normalise(jobicyItem(0, ['jobTitle' => 'PHP Developer (Remote, Europe)', 'jobGeo' => 'Germany, USA']));

    expect(JobFields::of($job)['eligibility'])->toBe(JobFields::restricted(['DE']));
});
