<?php

use App\List\JobState;
use App\Models\HiddenJob;
use App\Models\Job;
use App\Storage\JobStore;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Api\ApiHelpers;

uses(RefreshDatabase::class);

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-11-02T10:00:00Z');
    ApiHelpers::settings();
    Http::fake(['*/menu-bar/label' => Http::response()]);
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

/**
 * Stores a job and hides it at the given time.
 */
function hiddenJob(string $id, string $hiddenAt, array $overrides = []): Job
{
    $job = ApiHelpers::job($id, $overrides);
    CarbonImmutable::setTestNow($hiddenAt);
    app(JobStore::class)->setState($job, JobState::Hidden);
    CarbonImmutable::setTestNow('2026-11-02T10:00:00Z');

    return $job->refresh();
}

/**
 * The job is gone, as after retention. Its tombstones stay.
 */
function deletedByRetention(Job $job): void
{
    Job::query()->whereKey($job->id)->delete();
}

it('lists the stored hidden jobs, newest first, with the count of older hidden jobs', function () {
    $first = hiddenJob('1', '2026-11-01T08:00:00Z', ['title' => 'PHP Developer', 'company' => 'Acme']);
    $second = hiddenJob('2', '2026-11-02T09:00:00Z', ['title' => 'Laravel Engineer', 'company' => 'Globex']);
    deletedByRetention(hiddenJob('3', '2026-10-01T08:00:00Z'));
    deletedByRetention(hiddenJob('4', '2026-10-02T08:00:00Z'));
    ApiHelpers::job('5');

    $this->getJson('/api/hidden-jobs')
        ->assertOk()
        ->assertExactJson([
            'jobs' => [
                ['id' => $second->id, 'title' => 'Laravel Engineer', 'company' => 'Globex', 'hiddenAt' => '2026-11-02T09:00:00Z'],
                ['id' => $first->id, 'title' => 'PHP Developer', 'company' => 'Acme', 'hiddenAt' => '2026-11-01T08:00:00Z'],
            ],
            'olderCount' => 2,
        ]);
});

it('counts an older hidden job once, also with more than one source key', function () {
    $job = hiddenJob('1', '2026-10-01T08:00:00Z');
    HiddenJob::query()->create(['job_id' => $job->id, 'source' => 'other', 'source_job_id' => 'x-1', 'fingerprint' => $job->fingerprint]);
    deletedByRetention($job);

    $this->getJson('/api/hidden-jobs')->assertOk()->assertJsonPath('olderCount', 1);
});

it('gives an empty list with no hidden jobs', function () {
    ApiHelpers::job('1');

    $this->getJson('/api/hidden-jobs')->assertOk()->assertExactJson(['jobs' => [], 'olderCount' => 0]);
});

it('deletes only the tombstones of deleted jobs', function () {
    $stored = hiddenJob('1', '2026-11-01T08:00:00Z');
    deletedByRetention(hiddenJob('2', '2026-10-01T08:00:00Z'));

    $this->deleteJson('/api/hidden-jobs/older')->assertNoContent();

    expect(HiddenJob::query()->pluck('job_id')->all())->toBe([$stored->id])
        ->and($stored->refresh()->state)->toBe(JobState::Hidden);

    $this->getJson('/api/hidden-jobs')->assertJsonPath('olderCount', 0)->assertJsonCount(1, 'jobs');
});

it('keeps a stored hidden job hidden after the clear', function () {
    hiddenJob('1', '2026-11-01T08:00:00Z');

    $this->deleteJson('/api/hidden-jobs/older')->assertNoContent();

    // The source lists the job again: its tombstone still blocks it.
    Job::query()->delete();
    ApiHelpers::job('1');

    expect(Job::query()->count())->toBe(0);
});

it('shows a hidden job again through the state endpoint', function () {
    $job = hiddenJob('1', '2026-11-01T08:00:00Z');

    $this->putJson("/api/jobs/{$job->id}/state", ['state' => 'hidden'])->assertOk()->assertJsonPath('state', null);

    $this->getJson('/api/hidden-jobs')->assertExactJson(['jobs' => [], 'olderCount' => 0]);
    expect(HiddenJob::query()->count())->toBe(0);
});
