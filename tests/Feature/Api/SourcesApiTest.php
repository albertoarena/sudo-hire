<?php

use App\Models\SourceRun;
use App\Sources\SourceRegistry;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\FakeSource;
use Tests\Feature\Api\ApiHelpers;

uses(RefreshDatabase::class);

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-11-02T10:00:00Z');
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

it('gives every source with its status', function () {
    ApiHelpers::settings(['disabledSources' => ['jobicy']]);
    SourceRun::query()->create(['source' => 'larajobs', 'started_at' => '2026-11-02T06:00:00Z', 'finished_at' => '2026-11-02T06:00:05Z', 'items_fetched' => 3, 'items_skipped' => 0]);
    SourceRun::query()->create(['source' => 'larajobs', 'started_at' => '2026-11-02T08:00:00Z', 'finished_at' => '2026-11-02T08:00:02Z', 'error' => 'cURL error 60: SSL certificate problem', 'error_kind' => 'certificate']);
    ApiHelpers::job('1', ['source' => 'larajobs', 'attribution' => 'via LaraJobs']);

    $sources = collect($this->getJson('/api/sources')->assertOk()->json())->keyBy('key');

    expect($sources->keys()->all())->toBe(['ats', 'larajobs', 'jobicy', 'remotive', 'himalayas', 'weworkremotely'])
        ->and($sources['larajobs'])->toBe([
            'key' => 'larajobs',
            'name' => 'LaraJobs',
            'enabled' => true,
            'minIntervalMinutes' => 120,
            'lastRunAt' => '2026-11-02T08:00:00Z',
            'lastSuccessAt' => '2026-11-02T06:00:05Z',
            'jobCount' => 1,
            'lastError' => ['message' => 'cURL error 60: SSL certificate problem', 'kind' => 'certificate'],
        ])
        ->and($sources['jobicy']['enabled'])->toBeFalse()
        ->and($sources['ats']['name'])->toBe('Company careers')
        ->and($sources['weworkremotely']['name'])->toBe('We Work Remotely')
        ->and($sources['remotive'])->toMatchArray(['lastRunAt' => null, 'lastSuccessAt' => null, 'jobCount' => 0, 'lastError' => null]);
});

it('gives no error when the last run succeeded', function () {
    app()->instance(SourceRegistry::class, new SourceRegistry([new FakeSource]));
    SourceRun::query()->create(['source' => 'fake', 'started_at' => '2026-11-02T06:00:00Z', 'finished_at' => '2026-11-02T06:00:01Z', 'error' => 'Timeout', 'error_kind' => 'http']);
    SourceRun::query()->create(['source' => 'fake', 'started_at' => '2026-11-02T08:00:00Z', 'finished_at' => '2026-11-02T08:00:01Z', 'items_fetched' => 1, 'items_skipped' => 0]);
    ApiHelpers::job('1');
    ApiHelpers::job('2', ['source' => 'other', 'sourceJobId' => 'x-1', 'title' => 'Laravel Developer 1', 'attribution' => 'via Other']);

    expect($this->getJson('/api/sources')->assertOk()->json('0'))->toBe([
        'key' => 'fake',
        'name' => 'Fake',
        'enabled' => true,
        'minIntervalMinutes' => 60,
        'lastRunAt' => '2026-11-02T08:00:00Z',
        'lastSuccessAt' => '2026-11-02T08:00:01Z',
        'jobCount' => 1,
        'lastError' => null,
    ]);
});

it('counts the jobs that list the source as "also on"', function () {
    app()->instance(SourceRegistry::class, new SourceRegistry([new FakeSource]));
    ApiHelpers::job('1', ['source' => 'other', 'attribution' => 'via Other']);
    ApiHelpers::job('2', ['title' => 'Laravel Developer 1']);

    expect($this->getJson('/api/sources')->assertOk()->json('0.jobCount'))->toBe(1);
});
