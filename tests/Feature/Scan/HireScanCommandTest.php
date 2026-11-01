<?php

use App\Models\Job;
use App\Models\SentNotification;
use App\Models\SourceRun;
use App\Models\StoredSettings;
use App\Scan\Notifier;
use App\Schema\Eligibility;
use App\Schema\NormalisedJob;
use App\Schema\Salary;
use App\Schema\SalaryPeriod;
use App\Sources\LaraJobs\LaraJobsSource;
use App\Sources\SourceRegistry;
use App\Storage\JobStore;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Fakes\FakeNotifier;
use Tests\Fakes\FakeSource;
use Tests\Support\JobFields;
use Tests\Unit\Filters\Make;

uses(RefreshDatabase::class);

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-10-29T10:00:00Z');
    $this->notifier = new FakeNotifier;
    app()->instance(Notifier::class, $this->notifier);
    app()->instance(SourceRegistry::class, new SourceRegistry([]));
    commandSettings();
    Http::fake(['www.ecb.europa.eu/*' => Http::response(JobFields::fixture('ecb/eurofxref-daily.xml'))]);
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

/**
 * Stores the settings. The country is IT unless the test changes it.
 */
function commandSettings(array $overrides = []): void
{
    StoredSettings::query()->delete();
    StoredSettings::query()->create(['data' => Make::settings(array_merge(['country' => 'IT'], $overrides))->toArray()]);
}

/**
 * Stores one job, open worldwide unless the test changes it.
 */
function commandJob(array $overrides): void
{
    app(JobStore::class)->save([new NormalisedJob(...jobAttributes(array_merge([
        'eligibility' => Eligibility::worldwide(),
        'postedAt' => CarbonImmutable::parse('2026-10-28T08:00:00Z'),
        'fetchedAt' => CarbonImmutable::now(),
    ], $overrides)))]);
}

it('prints the matches with the salary, the eligibility, the source and the apply link', function () {
    commandSettings(['keywordsInclude' => ['Laravel', 'PHP']]);
    commandJob([
        'sourceJobId' => '1',
        'title' => 'Senior Laravel Developer',
        'company' => 'Acme',
        'eligibility' => Eligibility::restricted(['eu'], ['GB']),
        'salary' => new Salary(100000, null, 'USD', SalaryPeriod::Year, '$100k'),
    ]);
    commandJob([
        'sourceJobId' => '2',
        'title' => 'PHP Engineer',
        'company' => 'Globex',
        'eligibility' => Eligibility::worldwide(),
        'applyUrl' => 'https://example.com/jobs/2/apply',
    ]);
    commandJob(['sourceJobId' => '3', 'title' => 'PHP Developer', 'company' => 'Initech', 'eligibility' => Eligibility::unknown()]);

    $this->artisan('hire:scan')
        ->expectsTable(
            ['Title', 'Company', 'Salary', 'Eligibility', 'Source', 'Posted', 'Apply'],
            [
                ['Senior Laravel Developer', 'Acme', '$100k (EUR 89,823 a year)', 'eu, GB', 'via Fake', '2026-10-28', 'https://example.com/jobs/42/apply'],
                ['PHP Engineer', 'Globex', 'not stated', 'Worldwide', 'via Fake', '2026-10-28', 'https://example.com/jobs/2/apply'],
            ],
        )
        ->expectsOutputToContain('Location not stated: 1 job, not listed.')
        ->expectsOutputToContain('Notification sent: 2 new jobs: 1 Laravel, 1 PHP')
        ->assertExitCode(0);
});

it('prints one line for each source run and exits with 0 when a source fails', function () {
    Http::fake([
        'www.ecb.europa.eu/*' => Http::response(JobFields::fixture('ecb/eurofxref-daily.xml')),
        'larajobs.com/*' => Http::failedConnection('cURL error 60: SSL certificate problem: unable to get local issuer certificate'),
    ]);
    app()->instance(SourceRegistry::class, new SourceRegistry([new LaraJobsSource, new FakeSource]));
    // The FakeSource jobs are up to 74 hours old.
    commandSettings(['postedWithinHours' => 168]);

    $this->artisan('hire:scan')
        ->expectsOutputToContain('larajobs: 0 items, error: certificate')
        ->expectsOutputToContain('fake: 3 items')
        ->expectsOutputToContain('Notification sent: 2 new jobs')
        ->assertExitCode(0);
});

it('says when no source was due and there are no matches', function () {
    $this->artisan('hire:scan')
        ->expectsOutputToContain('No source was due.')
        ->expectsOutputToContain('No new matches.')
        ->assertExitCode(0);

    expect($this->notifier->sent)->toBe([]);
});

it('says when the notification is not due', function () {
    SentNotification::query()->create(['sent_at' => CarbonImmutable::parse('2026-10-29T09:30:00Z'), 'job_count' => 1, 'text' => '1 new job']);
    commandJob(['sourceJobId' => '1']);

    $this->artisan('hire:scan')
        ->expectsOutputToContain('Notification not due: the last one was sent at 2026-10-29 09:30 UTC.')
        ->assertExitCode(0);

    expect($this->notifier->sent)->toBe([]);
});

it('prints the matches and sends nothing with --no-notify', function () {
    commandJob(['sourceJobId' => '1']);

    $this->artisan('hire:scan', ['--no-notify' => true])
        ->expectsOutputToContain('Senior Laravel Developer')
        ->expectsOutputToContain('Notification off (--no-notify).')
        ->assertExitCode(0);

    expect($this->notifier->sent)->toBe([])
        ->and(Job::query()->whereNotNull('notified_at')->count())->toBe(0);
});

it('says when the notification failed and still exits with 0', function () {
    $this->notifier->fails = true;
    commandJob(['sourceJobId' => '1']);

    $this->artisan('hire:scan')
        ->expectsOutputToContain('Notification failed: osascript failed.')
        ->assertExitCode(0);
});

it('says when there is no country and sends nothing', function () {
    commandSettings(['country' => null]);
    commandJob(['sourceJobId' => '1']);

    $this->artisan('hire:scan')
        ->expectsOutputToContain('Senior Laravel Developer')
        ->expectsOutputToContain('No notification: set your country first.')
        ->assertExitCode(0);

    expect($this->notifier->sent)->toBe([])
        ->and(Job::query()->whereNotNull('notified_at')->count())->toBe(0);
});

it('is isolated, also without the --isolated option', function () {
    $command = Artisan::all()['hire:scan'];

    expect($command)->toBeInstanceOf(Isolatable::class);

    // A running scan holds the lock.
    Cache::lock('framework'.DIRECTORY_SEPARATOR.'command-hire:scan', 3600)->get();
    app()->instance(SourceRegistry::class, new SourceRegistry([new FakeSource]));

    $this->artisan('hire:scan')->assertExitCode(Command::SUCCESS);

    expect(SourceRun::query()->count())->toBe(0)
        ->and($this->notifier->sent)->toBe([]);
});

it('runs again when the lock is free', function () {
    commandJob(['sourceJobId' => '1']);

    $this->artisan('hire:scan')->assertExitCode(0);
    $this->artisan('hire:scan')->assertExitCode(0);

    expect($this->notifier->sent)->toBe(['1 new job']);
});

it('frees a lock that a killed scan left with cache:clear --locks, not with cache:clear', function () {
    // .env.example uses the database cache store. The locks are in the cache_locks table.
    config(['cache.default' => 'database']);
    app()->instance(SourceRegistry::class, new SourceRegistry([new FakeSource]));
    Cache::store('database')->lock('framework'.DIRECTORY_SEPARATOR.'command-hire:scan', 3600)->get();

    $this->artisan('cache:clear')->assertSuccessful();
    $this->artisan('hire:scan')->expectsOutputToContain('The [hire:scan] command is already running.');
    expect(SourceRun::query()->count())->toBe(0);

    $this->artisan('cache:clear', ['--locks' => true])->assertSuccessful();
    $this->artisan('hire:scan')->assertExitCode(0);
    expect(SourceRun::query()->count())->toBe(1);
});
