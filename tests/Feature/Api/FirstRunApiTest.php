<?php

use App\Models\SourceRun;
use App\Models\StoredSettings;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Api\ApiHelpers;

uses(RefreshDatabase::class);

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-11-02T10:00:00Z');
    // Two enabled sources: the company career pages (every 6 hours) and LaraJobs (every 2 hours).
    ApiHelpers::settings(['country' => 'IT', 'disabledSources' => ['jobicy', 'remotive', 'himalayas', 'weworkremotely']]);
    Http::fake(['*child-process/start-php' => Http::response(['pid' => 42, 'settings' => []]), '*' => Http::response()]);
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

function sourceRun(string $source, string $startedAt, ?string $finishedAt, ?string $errorKind = null): void
{
    SourceRun::query()->create([
        'source' => $source,
        'started_at' => CarbonImmutable::parse($startedAt),
        'finished_at' => $finishedAt === null ? null : CarbonImmutable::parse($finishedAt),
        'error_kind' => $errorKind,
        'error' => $errorKind === null ? null : 'The site did not answer.',
    ]);
}

/**
 * @return array<string, string> the status of each source, by key
 */
function firstScanStatus(): array
{
    $sources = test()->getJson('/api/first-scan')->assertOk()->json('sources');

    return array_column($sources, 'status', 'key');
}

it('starts hire:scan in the background without waiting for it', function () {
    $this->postJson('/api/first-scan')->assertAccepted();

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), 'child-process/start-php')
        && $request['alias'] === 'first-scan'
        && $request['cmd'] === ['artisan', 'hire:scan']
        && $request['persistent'] === false);
});

it('refuses to start the first scan with no country', function () {
    StoredSettings::query()->delete();

    $this->postJson('/api/first-scan')->assertStatus(422)->assertExactJson(['message' => 'Choose your country first.']);

    Http::assertNothingSent();
});

it('lists the enabled sources with their names, in registry order', function () {
    $this->postJson('/api/first-scan');

    $sources = $this->getJson('/api/first-scan')->assertOk()->json('sources');

    expect(array_column($sources, 'name', 'key'))->toBe(['ats' => 'Company careers', 'larajobs' => 'LaraJobs']);
});

it('shows a due source as waiting until its run starts, then reading, then done', function () {
    $this->postJson('/api/first-scan');

    expect(firstScanStatus())->toBe(['ats' => 'waiting', 'larajobs' => 'waiting'])
        ->and($this->getJson('/api/first-scan')->json('finished'))->toBeFalse();

    CarbonImmutable::setTestNow('2026-11-02T10:00:05Z');
    sourceRun('ats', '2026-11-02T10:00:01Z', '2026-11-02T10:00:04Z');
    sourceRun('larajobs', '2026-11-02T10:00:04Z', null);

    expect(firstScanStatus())->toBe(['ats' => 'done', 'larajobs' => 'reading']);

    SourceRun::query()->where('source', 'larajobs')->update(['finished_at' => CarbonImmutable::parse('2026-11-02T10:00:06Z')]);

    expect(firstScanStatus())->toBe(['ats' => 'done', 'larajobs' => 'done'])
        ->and($this->getJson('/api/first-scan')->json('finished'))->toBeTrue();
});

it('shows a failed run as failed, with its error kind', function () {
    $this->postJson('/api/first-scan');
    sourceRun('ats', '2026-11-02T10:00:01Z', '2026-11-02T10:00:21Z', 'http');

    $sources = collect($this->getJson('/api/first-scan')->json('sources'))->keyBy('key');

    expect($sources['ats']['status'])->toBe('failed')
        ->and($sources['ats']['errorKind'])->toBe('http')
        ->and($sources['larajobs']['errorKind'])->toBeNull();
});

it('shows a source that is not due as done: an earlier scan read it', function () {
    // The scheduler reads the sources while the first run is open, before a country is stored.
    sourceRun('ats', '2026-11-02T09:58:00Z', '2026-11-02T09:58:10Z');
    sourceRun('larajobs', '2026-11-02T09:58:10Z', '2026-11-02T09:58:20Z', 'http');

    $this->postJson('/api/first-scan');

    expect(firstScanStatus())->toBe(['ats' => 'done', 'larajobs' => 'failed']);
});

it('shows a run that an earlier scan still runs as reading', function () {
    sourceRun('ats', '2026-11-02T09:59:50Z', null);

    $this->postJson('/api/first-scan');

    expect(firstScanStatus())->toBe(['ats' => 'reading', 'larajobs' => 'waiting']);
});
it('serves the page that mounts the first run', function () {
    $this->withSession(['_token' => 'page-token'])
        ->get('/first-run')
        ->assertOk()
        ->assertSee('id="first-run"', false)
        ->assertSee('<meta name="csrf-token" content="page-token">', false);
});
