<?php

use App\List\JobState;
use App\Models\AlsoOnSource;
use App\Models\HiddenJob;
use App\Models\Job;
use App\Schema\NormalisedJob;
use App\Storage\JobStore;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-11-02T10:00:00Z');
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

function stateJob(array $overrides = []): NormalisedJob
{
    return new NormalisedJob(...jobAttributes(array_merge(['fetchedAt' => CarbonImmutable::now()], $overrides)));
}

function savedJob(array $overrides = []): Job
{
    $job = stateJob($overrides);
    app(JobStore::class)->save([$job]);

    return Job::query()->where('source', $job->source)->where('source_job_id', $job->sourceJobId)->sole();
}

/**
 * The same job on a second board: same company and title, other links.
 */
function sameJobOnOtherBoard(array $overrides = []): NormalisedJob
{
    return stateJob(array_merge([
        'source' => 'other',
        'sourceJobId' => 'x-7',
        'sourceUrl' => 'https://other.example/jobs/7',
        'applyUrl' => 'https://other.example/jobs/7/apply',
        'attribution' => 'via Other',
    ], $overrides));
}

it('stores a new job with no state and not seen', function () {
    $job = savedJob();

    expect($job->state)->toBeNull()
        ->and($job->state_at)->toBeNull()
        ->and($job->seen_at)->toBeNull();
});

it('saves, applies and hides a job, one state at a time', function (JobState $state) {
    $job = app(JobStore::class)->setState(savedJob(), $state);

    expect($job->state)->toBe($state)
        ->and($job->state_at?->toIso8601ZuluString())->toBe('2026-11-02T10:00:00Z')
        ->and(Job::query()->sole()->state)->toBe($state);
})->with([JobState::Saved, JobState::Applied, JobState::Hidden]);

it('replaces the state when another state is set', function () {
    $store = app(JobStore::class);
    $job = $store->setState(savedJob(), JobState::Saved);

    CarbonImmutable::setTestNow('2026-11-02T11:00:00Z');
    $job = $store->setState($job, JobState::Applied);

    expect($job->state)->toBe(JobState::Applied)
        ->and($job->state_at?->toIso8601ZuluString())->toBe('2026-11-02T11:00:00Z');
});

it('moves the job back to New when the same state is set again', function (JobState $state) {
    $store = app(JobStore::class);
    $job = $store->setState(savedJob(), $state);
    $job = $store->setState($job, $state);

    expect($job->state)->toBeNull()
        ->and($job->state_at)->toBeNull()
        ->and(Job::query()->sole()->state)->toBeNull();
})->with([JobState::Saved, JobState::Applied, JobState::Hidden]);

it('clears the state when the state is null', function () {
    $store = app(JobStore::class);
    $job = $store->setState(savedJob(), JobState::Saved);

    expect($store->setState($job, null)->state)->toBeNull();
});

it('marks the job as seen on any state change', function (?JobState $state) {
    $job = app(JobStore::class)->setState(savedJob(), $state);

    expect($job->seen_at?->toIso8601ZuluString())->toBe('2026-11-02T10:00:00Z');
})->with([JobState::Saved, JobState::Applied, JobState::Hidden, null]);

it('keeps the first seen time on a later state change', function () {
    $store = app(JobStore::class);
    $job = $store->setState(savedJob(), JobState::Saved);

    CarbonImmutable::setTestNow('2026-11-02T12:00:00Z');

    expect($store->setState($job, JobState::Saved)->seen_at?->toIso8601ZuluString())->toBe('2026-11-02T10:00:00Z');
});

it('keeps the state when the source lists the job again', function () {
    $store = app(JobStore::class);
    $store->setState(savedJob(), JobState::Saved);

    $store->save([stateJob(['title' => 'Senior Laravel Developer (EU)'])]);

    expect(Job::query()->sole()->state)->toBe(JobState::Saved)
        ->and(Job::query()->sole()->title)->toBe('Senior Laravel Developer (EU)');
});

it('never prunes a saved or an applied job', function (JobState $state) {
    $store = app(JobStore::class);
    $job = $store->setState(savedJob(['fetchedAt' => CarbonImmutable::now()->subDays(40)]), $state);
    $store->save([sameJobOnOtherBoard(['fetchedAt' => CarbonImmutable::now()->subDays(40)])]);
    Job::query()->update(['fetched_at' => CarbonImmutable::now()->subDays(40)]);

    expect($store->prune())->toBe(0)
        ->and(Job::query()->sole()->id)->toBe($job->id)
        ->and(AlsoOnSource::query()->count())->toBe(1);
})->with([JobState::Saved, JobState::Applied]);

it('prunes a hidden job as usual and keeps its tombstones', function () {
    $store = app(JobStore::class);
    $store->setState(savedJob(['fetchedAt' => CarbonImmutable::now()->subDays(31)]), JobState::Hidden);

    expect($store->prune())->toBe(1)
        ->and(Job::query()->count())->toBe(0)
        ->and(HiddenJob::query()->count())->toBe(1);

    $store->save([stateJob()]);

    expect(Job::query()->count())->toBe(0);
});

it('stores the keys of the job, of its "also on" rows and its fingerprint when it is hidden', function () {
    $store = app(JobStore::class);
    $job = savedJob();
    $store->save([sameJobOnOtherBoard()]);

    $store->setState($job, JobState::Hidden);

    expect(HiddenJob::query()->orderBy('id')->get(['job_id', 'source', 'source_job_id', 'fingerprint'])->toArray())->toBe([
        ['job_id' => $job->id, 'source' => 'fake', 'source_job_id' => '42', 'fingerprint' => $job->fingerprint],
        ['job_id' => $job->id, 'source' => 'other', 'source_job_id' => 'x-7', 'fingerprint' => $job->fingerprint],
    ]);
});

it('does not store a hidden job that is fetched again', function (NormalisedJob $again) {
    $store = app(JobStore::class);
    $store->save([sameJobOnOtherBoard()]);
    $store->setState(Job::query()->sole(), JobState::Hidden);
    Job::query()->delete();
    AlsoOnSource::query()->delete();

    $store->save([$again]);

    expect(Job::query()->count())->toBe(0)
        ->and(AlsoOnSource::query()->count())->toBe(0);
})->with([
    'the same key' => fn () => sameJobOnOtherBoard(),
    'an "also on" key' => fn () => stateJob(['source' => 'other', 'sourceJobId' => 'x-7', 'title' => 'Another title']),
    'the same fingerprint from another source' => fn () => stateJob(['source' => 'third', 'sourceJobId' => '9', 'attribution' => 'via Third']),
]);

it('blocks the same fingerprint from another source for 14 days only', function () {
    $store = app(JobStore::class);
    $store->setState(savedJob(), JobState::Hidden);
    $third = fn () => stateJob(['source' => 'third', 'sourceJobId' => '9', 'attribution' => 'via Third']);

    CarbonImmutable::setTestNow('2026-11-16T10:00:00Z');
    $store->save([$third()]);

    expect(Job::query()->where('source', 'third')->exists())->toBeFalse();

    CarbonImmutable::setTestNow('2026-11-16T10:00:01Z');
    $store->save([$third()]);

    expect(Job::query()->where('source', 'third')->exists())->toBeTrue()
        ->and(AlsoOnSource::query()->count())->toBe(0);
});

it('blocks the source keys of a hidden job forever', function () {
    $store = app(JobStore::class);
    $store->save([sameJobOnOtherBoard()]);
    $store->setState(Job::query()->sole(), JobState::Hidden);
    Job::query()->delete();

    CarbonImmutable::setTestNow('2027-11-02T10:00:00Z');
    $store->save([sameJobOnOtherBoard(['title' => 'Another title'])]);

    expect(Job::query()->count())->toBe(0);
});

it('does not update a hidden job that its source lists again', function () {
    $store = app(JobStore::class);
    $store->setState(savedJob(), JobState::Hidden);

    CarbonImmutable::setTestNow('2026-11-03T10:00:00Z');
    $store->save([stateJob(['title' => 'Senior Laravel Developer (EU)'])]);

    expect(Job::query()->sole()->title)->toBe('Senior Laravel Developer')
        ->and(Job::query()->sole()->fetched_at->toIso8601ZuluString())->toBe('2026-11-02T10:00:00Z');
});

it('does not add an "also on" row to a hidden job', function () {
    $store = app(JobStore::class);
    $store->setState(savedJob(), JobState::Hidden);

    $store->save([sameJobOnOtherBoard()]);

    expect(AlsoOnSource::query()->count())->toBe(0)
        ->and(Job::query()->count())->toBe(1);
});

it('stores other jobs as usual', function () {
    $store = app(JobStore::class);
    $store->setState(savedJob(), JobState::Hidden);

    $store->save([stateJob(['sourceJobId' => '43', 'title' => 'PHP Engineer'])]);

    expect(Job::query()->count())->toBe(2);
});

it('deletes the tombstones of the job on undo', function (?JobState $next) {
    $store = app(JobStore::class);
    $job = savedJob();
    $store->save([sameJobOnOtherBoard()]);
    $other = savedJob(['sourceJobId' => '43', 'title' => 'PHP Engineer']);
    $store->setState($other, JobState::Hidden);
    $job = $store->setState($job, JobState::Hidden);

    $store->setState($job, $next);

    expect(HiddenJob::query()->pluck('job_id')->all())->toBe([$other->id]);

    $store->save([stateJob(['title' => 'Senior Laravel Developer (EU)'])]);

    expect(Job::query()->find($job->id)?->title)->toBe('Senior Laravel Developer (EU)');
})->with([
    'hidden again' => JobState::Hidden,
    'null' => null,
    'saved' => JobState::Saved,
]);
