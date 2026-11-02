<?php

use App\List\JobState;
use App\Models\HiddenJob;
use App\Schema\Eligibility;
use App\Storage\JobStore;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
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

it('gives the list with its counts', function () {
    $job = ApiHelpers::job('1');
    $group = ApiHelpers::job('2', ['eligibility' => Eligibility::unknown()]);

    $this->getJson('/api/list')
        ->assertOk()
        ->assertJsonPath('counts', ['new' => 1, 'saved' => 0, 'applied' => 0, 'locationNotStated' => 1, 'unseen' => 1])
        ->assertJsonPath('new.0.id', $job->id)
        ->assertJsonPath('new.0.attribution', 'via Fake')
        ->assertJsonPath('new.0.stamp', ['kind' => 'admitted', 'place' => 'Location'])
        ->assertJsonPath('locationNotStated.0.id', $group->id)
        ->assertJsonPath('saved', [])
        ->assertJsonPath('applied', [])
        ->assertJsonStructure(['new' => [['id', 'title', 'company', 'source', 'attribution', 'alsoOn', 'postedAt', 'salary' => ['original', 'converted', 'unknown', 'ote'], 'terms' => ['contractType', 'seniority'], 'stamp' => ['kind', 'place'], 'sourceUrl', 'applyUrl', 'seen', 'state']]]);
});

it('saves a job and gives the new state and the counts', function () {
    $job = ApiHelpers::job('1');

    $this->putJson("/api/jobs/{$job->id}/state", ['state' => 'saved'])
        ->assertOk()
        ->assertExactJson(['state' => 'saved', 'counts' => ['new' => 0, 'saved' => 1, 'applied' => 0, 'locationNotStated' => 0, 'unseen' => 0]]);

    expect($job->fresh()?->state)->toBe(JobState::Saved)
        ->and($job->fresh()?->seen_at)->not->toBeNull();
});

it('moves the job back to New when the same state is sent again', function () {
    $job = ApiHelpers::job('1');
    app(JobStore::class)->setState($job, JobState::Applied);

    $this->putJson("/api/jobs/{$job->id}/state", ['state' => 'applied'])
        ->assertOk()
        ->assertJsonPath('state', null)
        ->assertJsonPath('counts.new', 1)
        ->assertJsonPath('counts.unseen', 0);
});

it('hides a job and undoes it', function () {
    $job = ApiHelpers::job('1');

    $this->putJson("/api/jobs/{$job->id}/state", ['state' => 'hidden'])->assertOk()->assertJsonPath('counts.new', 0);
    expect(HiddenJob::query()->count())->toBe(1);

    $this->putJson("/api/jobs/{$job->id}/state", ['state' => null])->assertOk()->assertJsonPath('state', null)->assertJsonPath('counts.new', 1);
    expect(HiddenJob::query()->count())->toBe(0);
});

it('gives 404 with a message for an unknown job', function () {
    $this->putJson('/api/jobs/999/state', ['state' => 'saved'])
        ->assertNotFound()
        ->assertExactJson(['message' => 'Job not found.']);
});

it('gives 422 for a state that does not exist or is missing', function (array $body) {
    $job = ApiHelpers::job('1');

    $this->putJson("/api/jobs/{$job->id}/state", $body)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('state');
})->with([
    'unknown' => [['state' => 'archived']],
    'missing' => [[]],
]);

it('updates the badge after a state change', function () {
    config(['nativephp-internal.running' => true]);
    $job = ApiHelpers::job('1');
    ApiHelpers::job('2');

    $this->putJson("/api/jobs/{$job->id}/state", ['state' => 'saved'])->assertOk();

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), 'menu-bar/label') && $request['label'] === '1');
});
