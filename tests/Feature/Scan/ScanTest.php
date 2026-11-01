<?php

use App\Models\AlsoOnSource;
use App\Models\Job;
use App\Models\SentNotification;
use App\Models\SourceRun;
use App\Models\StoredSettings;
use App\Scan\Notifier;
use App\Scan\Scan;
use App\Scan\ScanMatch;
use App\Scan\ScanResult;
use App\Schema\Eligibility;
use App\Schema\NormalisedJob;
use App\Schema\Salary;
use App\Schema\SalaryPeriod;
use App\Sources\LaraJobs\LaraJobsSource;
use App\Sources\SourceRegistry;
use App\Storage\JobStore;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
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
    useSources([]);
    Http::fake(['www.ecb.europa.eu/*' => Http::response(JobFields::fixture('ecb/eurofxref-daily.xml'))]);
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

function useSources(array $sources): void
{
    app()->instance(SourceRegistry::class, new SourceRegistry($sources));
}

/**
 * Stores the settings as they are, with no check of the source keys.
 */
function scanSettings(array $overrides = []): void
{
    StoredSettings::query()->delete();
    StoredSettings::query()->create(['data' => Make::settings($overrides)->toArray()]);
}

/**
 * Stores one job, posted and fetched now. Null when it is stored as "also on".
 */
function storeJob(array $overrides = []): ?Job
{
    $job = new NormalisedJob(...jobAttributes(array_merge([
        'postedAt' => CarbonImmutable::now(),
        'fetchedAt' => CarbonImmutable::now(),
    ], $overrides)));

    app(JobStore::class)->save([$job]);

    return Job::query()->where('source', $job->source)->where('source_job_id', $job->sourceJobId)->first();
}

function scan(bool $notify = true): ScanResult
{
    return app(Scan::class)->run($notify);
}

/**
 * @return list<string>
 */
function matchedTitles(ScanResult $result): array
{
    return array_map(fn (ScanMatch $match) => $match->job->title, $result->matches);
}

it('refreshes the rates, runs the sources, prunes, then filters', function () {
    Http::fake([
        'www.ecb.europa.eu/*' => Http::response(JobFields::fixture('ecb/eurofxref-daily.xml')),
        'larajobs.com/*' => Http::response(JobFields::fixture('larajobs/feed.xml')),
    ]);
    useSources([new LaraJobsSource]);
    $old = storeJob(['sourceJobId' => 'old', 'title' => 'Old PHP Developer', 'fetchedAt' => CarbonImmutable::now()->subDays(31)]);
    $usd = storeJob(['sourceJobId' => 'usd', 'title' => 'PHP Developer', 'salary' => new Salary(100000, null, 'USD', SalaryPeriod::Year, '$100k')]);

    $result = scan();

    $urls = Http::recorded()->map(fn (array $pair) => $pair[0]->url())->values()->all();

    expect($urls[0])->toContain('www.ecb.europa.eu')
        ->and($urls[1])->toContain('larajobs.com')
        ->and(array_map(fn (SourceRun $run) => $run->source, $result->runs))->toBe(['larajobs'])
        ->and(Job::query()->whereKey($old->id)->exists())->toBeFalse()
        ->and(matchedTitles($result))->not->toContain('Old PHP Developer')
        ->and(matchedTitles($result))->toContain('PHP Developer');

    // The filter uses the rates of this scan: the USD salary converts to EUR.
    $match = collect($result->matches)->first(fn (ScanMatch $match) => $match->job->id === $usd->id);
    expect($match->salary)->toBe('$100k (EUR 89,823 a year)');
});

it('prunes after the runner, so a job that the runner fetches again stays', function () {
    useSources([new FakeSource]);
    // FakeSource item 1, last seen 31 days ago: older than the retention period.
    $seenAgain = storeJob([
        'sourceJobId' => '1',
        'title' => 'Senior Laravel Developer',
        'fetchedAt' => CarbonImmutable::now()->subDays(JobStore::RETENTION_DAYS + 1),
    ]);

    scan();

    // A prune before the runner deletes the job, and the runner stores it again with a new id.
    expect(Job::query()->whereKey($seenAgain->id)->exists())->toBeTrue()
        ->and($seenAgain->fresh()?->fetched_at->toIso8601ZuluString())->toBe('2026-10-29T10:00:00Z');
});

it('goes on when the rates fetch fails and when a source fails', function () {
    Http::fake([
        'www.ecb.europa.eu/*' => Http::response('Server error', 500),
        'larajobs.com/*' => Http::failedConnection('cURL error 60: SSL certificate problem: unable to get local issuer certificate'),
    ]);
    useSources([new LaraJobsSource, new FakeSource]);
    // The FakeSource jobs are up to 74 hours old.
    scanSettings(['postedWithinHours' => 168]);

    $result = scan();

    expect(array_map(fn (SourceRun $run) => [$run->source, $run->error_kind], $result->runs))->toBe([
        ['larajobs', 'certificate'],
        ['fake', null],
    ])
        ->and(matchedTitles($result))->toBe(['Senior Laravel Developer', 'PHP Engineer'])
        ->and($this->notifier->sent)->toBe(['2 new jobs']);
});

it('sends the summary text and stores the sent notification', function () {
    scanSettings(['keywordsInclude' => ['Laravel', 'PHP'], 'postedWithinHours' => 168]);
    useSources([new FakeSource]);

    $result = scan();

    $sent = SentNotification::query()->sole();

    expect($this->notifier->sent)->toBe(['2 new jobs: 1 Laravel, 1 PHP'])
        ->and($sent->text)->toBe('2 new jobs: 1 Laravel, 1 PHP')
        ->and($sent->job_count)->toBe(2)
        ->and($sent->sent_at->toIso8601ZuluString())->toBe('2026-10-29T10:00:00Z')
        ->and($result->sent?->is($sent))->toBeTrue();
});

it('sends no notification and stores nothing with 0 new matches', function () {
    scanSettings(['keywordsInclude' => ['django']]);
    storeJob();

    $result = scan();

    expect($this->notifier->sent)->toBe([])
        ->and(SentNotification::query()->count())->toBe(0)
        ->and($result->sent)->toBeNull()
        ->and(Job::query()->whereNotNull('notified_at')->count())->toBe(0);
});

it('sends one notification for each interval', function () {
    scanSettings(['notificationIntervalHours' => 2]);

    storeJob(['sourceJobId' => '1', 'title' => 'PHP Developer one']);
    scan();
    expect($this->notifier->sent)->toBe(['1 new job']);

    // Inside the interval: the new match waits.
    CarbonImmutable::setTestNow('2026-10-29T11:59:00Z');
    $waiting = storeJob(['sourceJobId' => '2', 'title' => 'PHP Developer two']);
    $result = scan();
    expect($this->notifier->sent)->toBe(['1 new job'])
        ->and($result->sent)->toBeNull()
        ->and($waiting->fresh()?->notified_at)->toBeNull()
        ->and(matchedTitles($result))->toBe(['PHP Developer two']);

    // After the interval: the waiting match is in the next notification.
    CarbonImmutable::setTestNow('2026-10-29T12:00:00Z');
    scan();
    expect($this->notifier->sent)->toBe(['1 new job', '1 new job'])
        ->and($waiting->fresh()?->notified_at?->toIso8601ZuluString())->toBe('2026-10-29T12:00:00Z');
});

it('does not reset the interval with a scan that has 0 matches', function () {
    scanSettings(['notificationIntervalHours' => 2]);
    storeJob(['sourceJobId' => '1', 'title' => 'PHP Developer one']);
    scan();

    CarbonImmutable::setTestNow('2026-10-29T13:00:00Z');
    scan();
    expect($this->notifier->sent)->toBe(['1 new job'])
        ->and(SentNotification::query()->count())->toBe(1);

    // The interval still counts from 10:00, so a match at 13:01 is sent at once.
    CarbonImmutable::setTestNow('2026-10-29T13:01:00Z');
    storeJob(['sourceJobId' => '2', 'title' => 'PHP Developer two']);
    scan();
    expect($this->notifier->sent)->toBe(['1 new job', '1 new job']);
});

it('sets notified_at on the counted jobs only', function () {
    scanSettings(['country' => 'IT', 'keywordsExclude' => ['java']]);
    $counted = storeJob(['sourceJobId' => '1', 'title' => 'PHP Developer', 'eligibility' => Eligibility::worldwide()]);
    $failing = storeJob(['sourceJobId' => '2', 'title' => 'Java Developer', 'eligibility' => Eligibility::worldwide()]);
    $grouped = storeJob(['sourceJobId' => '3', 'title' => 'Laravel Developer', 'eligibility' => Eligibility::unknown()]);

    $result = scan();

    expect($this->notifier->sent)->toBe(['1 new job'])
        ->and($counted->fresh()?->notified_at?->toIso8601ZuluString())->toBe('2026-10-29T10:00:00Z')
        ->and($failing->fresh()?->notified_at)->toBeNull()
        ->and($grouped->fresh()?->notified_at)->toBeNull();
});

it('does not count the "Location not stated" jobs and does not list them', function () {
    scanSettings(['country' => 'IT']);
    storeJob(['sourceJobId' => '1', 'title' => 'PHP Developer', 'eligibility' => Eligibility::worldwide()]);
    storeJob(['sourceJobId' => '2', 'title' => 'Laravel Developer']);
    storeJob(['sourceJobId' => '3', 'title' => 'Symfony Developer']);

    $result = scan();

    expect(matchedTitles($result))->toBe(['PHP Developer'])
        ->and($result->locationNotStated)->toBe(2)
        ->and($this->notifier->sent)->toBe(['1 new job']);
});

it('sends no notification when every match is in the "Location not stated" group', function () {
    scanSettings(['country' => 'IT']);
    storeJob();

    $result = scan();

    expect($this->notifier->sent)->toBe([])
        ->and($result->locationNotStated)->toBe(1)
        ->and(SentNotification::query()->count())->toBe(0);
});

it('never counts a notified job again', function () {
    storeJob();
    scan();

    CarbonImmutable::setTestNow('2026-10-29T20:00:00Z');
    $result = scan();

    expect($this->notifier->sent)->toBe(['1 new job'])
        ->and($result->matches)->toBe([]);
});

it('prints the matches but sends nothing and sets no notified_at with notify off', function () {
    storeJob();

    $result = scan(notify: false);

    expect(matchedTitles($result))->toBe(['Senior Laravel Developer'])
        ->and($this->notifier->sent)->toBe([])
        ->and(SentNotification::query()->count())->toBe(0)
        ->and(Job::query()->whereNotNull('notified_at')->count())->toBe(0);
});

it('keeps notified_at null and stores nothing when the notifier fails', function () {
    $this->notifier->fails = true;
    $job = storeJob();

    $result = scan();

    expect($result->sent)->toBeNull()
        ->and($result->notificationError)->toBe('osascript failed.')
        ->and(SentNotification::query()->count())->toBe(0)
        ->and($job->fresh()?->notified_at)->toBeNull();
});

it('passes a job from a disabled source with an enabled "also on" source, with the links of that source', function () {
    scanSettings(['disabledSources' => ['larajobs']]);
    $job = storeJob(['source' => 'larajobs', 'sourceJobId' => '1', 'title' => 'PHP Developer']);
    storeJob(['source' => 'remotive', 'sourceJobId' => 'r1', 'title' => 'PHP Developer', 'sourceUrl' => 'https://remotive.example/1', 'applyUrl' => 'https://remotive.example/1/apply', 'attribution' => 'via Remotive']);
    storeJob(['source' => 'jobicy', 'sourceJobId' => 'j1', 'title' => 'PHP Developer', 'sourceUrl' => 'https://jobicy.example/1', 'applyUrl' => 'https://jobicy.example/1/apply', 'attribution' => 'via Jobicy']);

    expect(AlsoOnSource::query()->count())->toBe(2);

    $result = scan();

    expect($result->matches)->toHaveCount(1)
        ->and($result->matches[0]->source)->toBe('remotive')
        ->and($result->matches[0]->sourceUrl)->toBe('https://remotive.example/1')
        ->and($result->matches[0]->applyUrl)->toBe('https://remotive.example/1/apply')
        ->and($result->matches[0]->attribution)->toBe('via Remotive')
        ->and($this->notifier->sent)->toBe(['1 new job']);

    // The stored links do not change.
    expect($job->fresh()?->apply_url)->toBe('https://example.com/jobs/42/apply')
        ->and($job->fresh()?->source)->toBe('larajobs');
});

it('skips a disabled "also on" source for the links', function () {
    scanSettings(['disabledSources' => ['larajobs', 'remotive']]);
    storeJob(['source' => 'larajobs', 'sourceJobId' => '1', 'title' => 'PHP Developer']);
    storeJob(['source' => 'remotive', 'sourceJobId' => 'r1', 'title' => 'PHP Developer', 'applyUrl' => 'https://remotive.example/1/apply']);
    storeJob(['source' => 'jobicy', 'sourceJobId' => 'j1', 'title' => 'PHP Developer', 'applyUrl' => 'https://jobicy.example/1/apply']);

    $result = scan();

    expect($result->matches[0]->source)->toBe('jobicy')
        ->and($result->matches[0]->applyUrl)->toBe('https://jobicy.example/1/apply');
});

it('uses the links of the main source when it is enabled', function () {
    storeJob(['source' => 'larajobs', 'sourceJobId' => '1', 'title' => 'PHP Developer']);
    storeJob(['source' => 'remotive', 'sourceJobId' => 'r1', 'title' => 'PHP Developer', 'applyUrl' => 'https://remotive.example/1/apply']);

    $result = scan();

    expect($result->matches[0]->source)->toBe('larajobs')
        ->and($result->matches[0]->applyUrl)->toBe('https://example.com/jobs/42/apply');
});

it('fails a job when its main source and every "also on" source are disabled', function () {
    scanSettings(['disabledSources' => ['larajobs', 'remotive']]);
    storeJob(['source' => 'larajobs', 'sourceJobId' => '1', 'title' => 'PHP Developer']);
    storeJob(['source' => 'remotive', 'sourceJobId' => 'r1', 'title' => 'PHP Developer']);

    $result = scan();

    expect($result->matches)->toBe([])
        ->and($this->notifier->sent)->toBe([]);
});

it('makes no live request', function () {
    useSources([new LaraJobsSource]);
    Http::preventStrayRequests();
    Http::fake([
        'www.ecb.europa.eu/*' => Http::response(JobFields::fixture('ecb/eurofxref-daily.xml')),
        'larajobs.com/*' => Http::response(JobFields::fixture('larajobs/feed.xml')),
    ]);

    scan();

    Http::assertSent(fn (Request $request) => str_contains($request->url(), 'larajobs.com'));
});
