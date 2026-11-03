<?php

use App\Models\AlsoOnSource;
use App\Models\Job;
use App\Schema\Eligibility;
use App\Schema\NormalisedJob;
use App\Schema\Salary;
use App\Schema\SalaryPeriod;
use App\Storage\JobStore;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\JobFields;

uses(RefreshDatabase::class);

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-10-29T10:00:00Z');
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

function storedJob(array $overrides = []): NormalisedJob
{
    return new NormalisedJob(...jobAttributes(array_merge(['fetchedAt' => CarbonImmutable::now()], $overrides)));
}

/**
 * The same job on a second board: same company and title, other links.
 */
function sameJobElsewhere(array $overrides = []): NormalisedJob
{
    return storedJob(array_merge([
        'source' => 'other',
        'sourceJobId' => 'x-7',
        'sourceUrl' => 'https://other.example/jobs/7',
        'applyUrl' => 'https://other.example/jobs/7/apply',
        'title' => 'Senior  laravel developer',
        'company' => 'ACME',
        'attribution' => 'via Other',
    ], $overrides));
}

it('stores a new job with every schema field', function () {
    $job = storedJob([
        'description' => 'Build things.',
        'tags' => ['php', 'laravel'],
        'eligibility' => Eligibility::restricted(['eu'], ['IT']),
        'officeLocation' => 'Milan',
        'timezoneMin' => -9.5,
        'timezoneMax' => 5.75,
        'salary' => new Salary(60000.0, 70000.0, 'EUR', SalaryPeriod::Year, '60-70k EUR'),
        'expiresAt' => CarbonImmutable::parse('2026-11-30T00:00:00Z'),
    ]);

    (new JobStore)->save([$job]);

    $stored = Job::query()->sole();

    expect(JobFields::of($stored->toNormalised()))->toBe(JobFields::of($job))
        ->and($stored->toNormalised()->description)->toBe('Build things.')
        ->and($stored->fingerprint)->toBe($job->fingerprint)
        ->and($stored->notified_at)->toBeNull();
});

it('updates a job that is already stored with the same source and id', function () {
    $store = new JobStore;
    $store->save([storedJob()]);
    Job::query()->update(['notified_at' => CarbonImmutable::now()]);

    CarbonImmutable::setTestNow('2026-10-30T10:00:00Z');
    $store->save([storedJob(['title' => 'Senior Laravel Developer (EU)'])]);

    $stored = Job::query()->sole();

    expect($stored->title)->toBe('Senior Laravel Developer (EU)')
        ->and($stored->fetched_at->toIso8601ZuluString())->toBe('2026-10-30T10:00:00Z')
        ->and($stored->notified_at?->toIso8601ZuluString())->toBe('2026-10-29T10:00:00Z');
});

it('keeps the first job and stores the second source as "also on" within 14 days', function () {
    $store = new JobStore;
    $store->save([storedJob()]);

    CarbonImmutable::setTestNow('2026-11-12T10:00:00Z');
    $store->save([sameJobElsewhere()]);

    $job = Job::query()->sole();
    $alsoOn = AlsoOnSource::query()->sole();

    expect($job->source)->toBe('fake')
        ->and($job->apply_url)->toBe('https://example.com/jobs/42/apply')
        ->and($alsoOn->job_id)->toBe($job->id)
        ->and($alsoOn->source)->toBe('other')
        ->and($alsoOn->source_job_id)->toBe('x-7')
        ->and($alsoOn->source_url)->toBe('https://other.example/jobs/7')
        ->and($alsoOn->apply_url)->toBe('https://other.example/jobs/7/apply')
        ->and($alsoOn->attribution)->toBe('via Other')
        ->and($job->alsoOn()->count())->toBe(1);
});

it('updates the "also on" row when the second source lists the job again', function () {
    $store = new JobStore;
    $store->save([storedJob()]);
    $store->save([sameJobElsewhere()]);

    CarbonImmutable::setTestNow('2026-11-20T10:00:00Z');
    $store->save([sameJobElsewhere(['applyUrl' => 'https://other.example/jobs/7/apply-now'])]);

    expect(Job::query()->count())->toBe(1)
        ->and(AlsoOnSource::query()->sole()->apply_url)->toBe('https://other.example/jobs/7/apply-now')
        ->and(AlsoOnSource::query()->sole()->fetched_at->toIso8601ZuluString())->toBe('2026-11-20T10:00:00Z')
        // The job was seen again, so retention counts from now.
        ->and(Job::query()->sole()->fetched_at->toIso8601ZuluString())->toBe('2026-11-20T10:00:00Z')
        ->and(Job::query()->sole()->apply_url)->toBe('https://example.com/jobs/42/apply');
});

it('stores two jobs when the second source comes after 14 days', function () {
    $store = new JobStore;
    $store->save([storedJob()]);

    CarbonImmutable::setTestNow('2026-11-12T10:00:01Z');
    $store->save([sameJobElsewhere()]);

    expect(Job::query()->count())->toBe(2)
        ->and(AlsoOnSource::query()->count())->toBe(0);
});

/**
 * The same job again on the same board, under another id and other links.
 */
function copyOnSameBoard(array $overrides = []): NormalisedJob
{
    return storedJob(array_merge([
        'sourceJobId' => '43',
        'sourceUrl' => 'https://example.com/jobs/43',
        'applyUrl' => 'https://example.com/jobs/43/apply',
    ], $overrides));
}

it('stores an identical copy from the same source as a row of the first job', function () {
    $store = new JobStore;
    $store->save([storedJob(), copyOnSameBoard()]);

    $job = Job::query()->sole();
    $copy = AlsoOnSource::query()->sole();

    expect($job->source_job_id)->toBe('42')
        ->and($job->source_url)->toBe('https://example.com/jobs/42')
        ->and($job->apply_url)->toBe('https://example.com/jobs/42/apply')
        ->and($copy->job_id)->toBe($job->id)
        ->and($copy->source)->toBe('fake')
        ->and($copy->source_job_id)->toBe('43')
        ->and($copy->source_url)->toBe('https://example.com/jobs/43')
        ->and($copy->apply_url)->toBe('https://example.com/jobs/43/apply')
        ->and($copy->attribution)->toBe('via Fake');
});

it('compares the eligibility and the salary of a copy from the same source', function (array $first, array $copy, int $jobs) {
    $store = new JobStore;
    $store->save([storedJob($first), copyOnSameBoard($copy)]);

    expect(Job::query()->count())->toBe($jobs)
        ->and(AlsoOnSource::query()->count())->toBe(2 - $jobs);
})->with([
    'countries in another order' => fn () => [
        ['eligibility' => Eligibility::restricted(['eu'], ['IT', 'ES'])],
        ['eligibility' => Eligibility::restricted(['eu'], ['ES', 'IT'])],
        1,
    ],
    'the same salary' => fn () => [
        ['salary' => new Salary(60000.0, 70000.0, 'EUR', SalaryPeriod::Year, '60-70k EUR')],
        ['salary' => new Salary(60000.0, 70000.0, 'EUR', SalaryPeriod::Year, 'EUR 60,000 - 70,000')],
        1,
    ],
    'another country' => fn () => [
        ['eligibility' => Eligibility::restricted([], ['IT'])],
        ['eligibility' => Eligibility::restricted([], ['ES'])],
        2,
    ],
    'another region' => fn () => [
        ['eligibility' => Eligibility::restricted(['eu'])],
        ['eligibility' => Eligibility::restricted(['north_america'])],
        2,
    ],
    'another eligibility type' => fn () => [
        ['eligibility' => Eligibility::worldwide()],
        ['eligibility' => Eligibility::unknown()],
        2,
    ],
    'another salary minimum' => fn () => [
        ['salary' => new Salary(60000.0, 70000.0, 'EUR', SalaryPeriod::Year, '60-70k EUR')],
        ['salary' => new Salary(65000.0, 70000.0, 'EUR', SalaryPeriod::Year, '65-70k EUR')],
        2,
    ],
    'another salary maximum' => fn () => [
        ['salary' => new Salary(60000.0, 70000.0, 'EUR', SalaryPeriod::Year, '60-70k EUR')],
        ['salary' => new Salary(60000.0, null, 'EUR', SalaryPeriod::Year, 'from 60k EUR')],
        2,
    ],
    'another salary currency' => fn () => [
        ['salary' => new Salary(60000.0, 70000.0, 'EUR', SalaryPeriod::Year, '60-70k EUR')],
        ['salary' => new Salary(60000.0, 70000.0, 'USD', SalaryPeriod::Year, '$60-70k')],
        2,
    ],
    'another salary period' => fn () => [
        ['salary' => new Salary(60000.0, 70000.0, 'EUR', SalaryPeriod::Year, '60-70k EUR')],
        ['salary' => new Salary(60000.0, 70000.0, 'EUR', SalaryPeriod::Month, '60-70k EUR')],
        2,
    ],
    'a salary and no salary' => fn () => [
        ['salary' => new Salary(60000.0, 70000.0, 'EUR', SalaryPeriod::Year, '60-70k EUR')],
        ['salary' => null],
        2,
    ],
]);

it('stores a copy from the same source with the first identical job', function () {
    $store = new JobStore;
    $store->save([
        storedJob(['eligibility' => Eligibility::restricted([], ['IT'])]),
        copyOnSameBoard(['eligibility' => Eligibility::restricted([], ['ES'])]),
        copyOnSameBoard(['sourceJobId' => '44', 'eligibility' => Eligibility::restricted([], ['ES'])]),
    ]);

    $spain = Job::query()->where('source_job_id', '43')->sole();

    expect(Job::query()->count())->toBe(2)
        ->and(AlsoOnSource::query()->sole()->job_id)->toBe($spain->id)
        ->and(AlsoOnSource::query()->sole()->source_job_id)->toBe('44')
        ->and(Job::query()->where('source_job_id', '42')->sole()->eligibility_countries)->toBe(['IT']);
});

it('stores a new job when the copy from the same source comes after 14 days', function () {
    $store = new JobStore;
    $store->save([storedJob()]);

    CarbonImmutable::setTestNow('2026-11-12T10:00:01Z');
    $store->save([copyOnSameBoard()]);

    expect(Job::query()->count())->toBe(2)
        ->and(AlsoOnSource::query()->count())->toBe(0);
});

it('updates the row of a copy from the same source when it is fetched again', function () {
    $store = new JobStore;
    $store->save([storedJob(), copyOnSameBoard()]);

    CarbonImmutable::setTestNow('2026-11-20T10:00:00Z');
    $store->save([copyOnSameBoard(['applyUrl' => 'https://example.com/jobs/43/apply-now'])]);

    $copy = AlsoOnSource::query()->sole();
    $job = Job::query()->sole();

    expect($copy->apply_url)->toBe('https://example.com/jobs/43/apply-now')
        ->and($copy->fetched_at->toIso8601ZuluString())->toBe('2026-11-20T10:00:00Z')
        ->and($job->fetched_at->toIso8601ZuluString())->toBe('2026-11-20T10:00:00Z')
        ->and($job->apply_url)->toBe('https://example.com/jobs/42/apply');
});

it('updates the job when its first copy is fetched again', function () {
    $store = new JobStore;
    $store->save([storedJob(), copyOnSameBoard()]);

    CarbonImmutable::setTestNow('2026-11-20T10:00:00Z');
    $store->save([storedJob(['applyUrl' => 'https://example.com/jobs/42/apply-now'])]);

    expect(Job::query()->sole()->apply_url)->toBe('https://example.com/jobs/42/apply-now')
        ->and(Job::query()->sole()->fetched_at->toIso8601ZuluString())->toBe('2026-11-20T10:00:00Z')
        ->and(AlsoOnSource::query()->sole()->source_job_id)->toBe('43');
});

it('stores another source as "also on" of a job with a copy from the same source', function () {
    $store = new JobStore;
    $store->save([storedJob(), copyOnSameBoard(), sameJobElsewhere()]);

    expect(Job::query()->count())->toBe(1)
        ->and(AlsoOnSource::query()->orderBy('id')->get(['source', 'source_job_id'])->toArray())->toBe([
            ['source' => 'fake', 'source_job_id' => '43'],
            ['source' => 'other', 'source_job_id' => 'x-7'],
        ]);
});

it('gives the jobs that are not notified yet', function () {
    $store = new JobStore;
    $store->save([storedJob(), storedJob(['sourceJobId' => '43', 'title' => 'PHP Engineer'])]);
    Job::query()->where('source_job_id', '42')->update(['notified_at' => CarbonImmutable::now()]);

    expect($store->notNotified()->pluck('source_job_id')->all())->toBe(['43']);
});

it('leaves out the jobs with a state or a seen time', function (array $columns) {
    $store = new JobStore;
    $store->save([storedJob(), storedJob(['sourceJobId' => '43', 'title' => 'PHP Engineer'])]);
    Job::query()->where('source_job_id', '42')->update($columns);

    expect($store->notNotified()->pluck('source_job_id')->all())->toBe(['43']);
})->with([
    'saved' => [['state' => 'saved', 'state_at' => '2026-10-29 10:00:00']],
    'applied' => [['state' => 'applied', 'state_at' => '2026-10-29 10:00:00']],
    'hidden' => [['state' => 'hidden', 'state_at' => '2026-10-29 10:00:00']],
    'seen' => [['seen_at' => '2026-10-29 10:00:00']],
]);

it('deletes the jobs not seen for 30 days, with their "also on" rows', function () {
    $store = new JobStore;
    $store->save([storedJob(), sameJobElsewhere()]);
    $store->save([storedJob(['sourceJobId' => '43', 'title' => 'PHP Engineer', 'fetchedAt' => CarbonImmutable::parse('2026-11-20T10:00:00Z')])]);

    CarbonImmutable::setTestNow('2026-11-28T10:00:01Z');
    $deleted = $store->prune();

    expect($deleted)->toBe(1)
        ->and(Job::query()->pluck('source_job_id')->all())->toBe(['43'])
        ->and(AlsoOnSource::query()->count())->toBe(0);
});

it('keeps a job seen 30 days ago to the second', function () {
    $store = new JobStore;
    $store->save([storedJob()]);

    CarbonImmutable::setTestNow('2026-11-28T10:00:00Z');

    expect($store->prune())->toBe(0)
        ->and(Job::query()->count())->toBe(1);
});
