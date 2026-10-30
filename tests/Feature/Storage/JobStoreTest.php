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

it('stores two jobs with the same fingerprint from the same source', function () {
    $store = new JobStore;

    $store->save([storedJob(), storedJob(['sourceJobId' => '43', 'sourceUrl' => 'https://example.com/jobs/43'])]);

    expect(Job::query()->count())->toBe(2)
        ->and(AlsoOnSource::query()->count())->toBe(0);
});

it('gives the jobs that are not notified yet', function () {
    $store = new JobStore;
    $store->save([storedJob(), storedJob(['sourceJobId' => '43', 'title' => 'PHP Engineer'])]);
    Job::query()->where('source_job_id', '42')->update(['notified_at' => CarbonImmutable::now()]);

    expect($store->notNotified()->pluck('source_job_id')->all())->toBe(['43']);
});

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
