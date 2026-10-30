<?php

use App\Models\Job;
use App\Models\SourceRun;
use App\Settings\Settings;
use App\Sources\Jobicy\JobicySource;
use App\Sources\LaraJobs\LaraJobsSource;
use App\Sources\SourceRegistry;
use App\Sources\SourceRunner;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\Fakes\FakeSource;
use Tests\Support\JobFields;

uses(RefreshDatabase::class);

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-10-29T10:00:00Z');
    $this->settings = Settings::defaults('UTC');
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

function runner(array $sources): SourceRunner
{
    return app()->makeWith(SourceRunner::class, ['registry' => new SourceRegistry($sources)]);
}

function fakeBoards(): void
{
    Http::fake([
        'larajobs.com/*' => Http::response(JobFields::fixture('larajobs/feed.xml')),
        'jobicy.com/*' => Http::response(JobFields::fixture('jobicy/remote-jobs.json')),
    ]);
}

it('runs each enabled source and stores its jobs', function () {
    fakeBoards();

    $runs = runner([new LaraJobsSource, new JobicySource])->run($this->settings);

    expect(array_map(fn (SourceRun $run) => $run->source, $runs))->toBe(['larajobs', 'jobicy'])
        ->and(Job::query()->where('source', 'larajobs')->count())->toBe(3)
        ->and(Job::query()->where('source', 'jobicy')->count())->toBe(3);
});

it('stores one row per run with the counts and the unknown shares', function () {
    fakeBoards();

    runner([new LaraJobsSource, new JobicySource])->run($this->settings);

    $larajobs = SourceRun::query()->where('source', 'larajobs')->sole();

    // LaraJobs fixture: 2 of 3 jobs have unknown eligibility (the hybrid job and the time zone only job), 1 of 3 has no salary.
    expect($larajobs->started_at->toIso8601ZuluString())->toBe('2026-10-29T10:00:00Z')
        ->and($larajobs->finished_at?->toIso8601ZuluString())->toBe('2026-10-29T10:00:00Z')
        ->and($larajobs->error)->toBeNull()
        ->and($larajobs->error_kind)->toBeNull()
        ->and($larajobs->items_fetched)->toBe(3)
        ->and($larajobs->items_skipped)->toBe(0)
        ->and($larajobs->unknown_eligibility_share)->toEqualWithDelta(2 / 3, 1e-9)
        ->and($larajobs->unknown_salary_share)->toEqualWithDelta(1 / 3, 1e-9);

    // Jobicy fixture: every location is read, 2 of 3 jobs have no salary.
    $jobicy = SourceRun::query()->where('source', 'jobicy')->sole();

    expect($jobicy->unknown_eligibility_share)->toBe(0.0)
        ->and($jobicy->unknown_salary_share)->toEqualWithDelta(2 / 3, 1e-9);
});

it('counts the skipped items', function () {
    runner([new FakeSource])->run($this->settings);

    $run = SourceRun::query()->sole();

    expect($run->items_fetched)->toBe(3)
        ->and($run->items_skipped)->toBe(1);
});

it('does not run a disabled source', function () {
    fakeBoards();
    $settings = Settings::fromArray(['disabledSources' => ['larajobs']]);

    $runs = runner([new LaraJobsSource, new JobicySource])->run($settings);

    expect(array_map(fn (SourceRun $run) => $run->source, $runs))->toBe(['jobicy']);
    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'larajobs.com'));
});

it('does not run a source before its minimum interval', function () {
    $source = new FakeSource;
    $runner = runner([$source]);

    $runner->run($this->settings);
    CarbonImmutable::setTestNow('2026-10-29T10:59:59Z');
    $second = $runner->run($this->settings);
    CarbonImmutable::setTestNow('2026-10-29T11:00:00Z');
    $third = $runner->run($this->settings);

    expect($second)->toBe([])
        ->and($third)->toHaveCount(1)
        ->and($source->fetchedSince)->toHaveCount(2);
});

it('waits the minimum interval after a failed run too', function () {
    Http::fake(['larajobs.com/*' => Http::response('Server error', 500)]);
    $runner = runner([new LaraJobsSource]);

    $runner->run($this->settings);
    CarbonImmutable::setTestNow('2026-10-29T11:00:00Z');

    expect($runner->run($this->settings))->toBe([])
        ->and(Http::recorded())->toHaveCount(1);
});

it('fetches with $since set to the start of the last successful run', function () {
    $source = new FakeSource;
    $runner = runner([$source]);

    $runner->run($this->settings);

    // A failed run in between does not move $since.
    SourceRun::query()->create([
        'source' => 'fake',
        'started_at' => CarbonImmutable::parse('2026-10-29T11:30:00Z'),
        'finished_at' => CarbonImmutable::parse('2026-10-29T11:30:01Z'),
        'error' => 'HTTP 503',
        'error_kind' => 'http',
    ]);

    CarbonImmutable::setTestNow('2026-10-29T13:00:00Z');
    $runner->run($this->settings);

    expect($source->fetchedSince[0])->toBeNull()
        ->and($source->fetchedSince[1]?->toIso8601ZuluString())->toBe('2026-10-29T10:00:00Z');
});

it('goes on with the next source when a source fails', function () {
    Log::spy();
    Http::fake([
        'larajobs.com/*' => Http::failedConnection('cURL error 60: SSL certificate problem: unable to get local issuer certificate'),
        'jobicy.com/*' => Http::response(JobFields::fixture('jobicy/remote-jobs.json')),
    ]);

    $runs = runner([new LaraJobsSource, new JobicySource])->run($this->settings);

    $failed = SourceRun::query()->where('source', 'larajobs')->sole();

    expect($runs)->toHaveCount(2)
        ->and($failed->error_kind)->toBe('certificate')
        ->and($failed->error)->toContain('cURL error 60')
        ->and($failed->finished_at)->not->toBeNull()
        ->and($failed->items_fetched)->toBe(0)
        ->and($failed->unknown_eligibility_share)->toBeNull()
        ->and(Job::query()->where('source', 'jobicy')->count())->toBe(3);

    Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message) => str_contains($message, 'larajobs') && str_contains($message, 'cURL error 60'));
});

it('stores the error kind', function (Closure $response, string $kind) {
    Http::fake(['larajobs.com/*' => $response()]);

    runner([new LaraJobsSource])->run($this->settings);

    expect(SourceRun::query()->sole()->error_kind)->toBe($kind);
})->with([
    'HTTP status' => [fn () => Http::response('Server error', 500), 'http'],
    'no connection' => [fn () => Http::failedConnection(), 'http'],
    'not RSS' => [fn () => Http::response('<!DOCTYPE html><html><body>Maintenance</body></html>'), 'parse'],
]);
