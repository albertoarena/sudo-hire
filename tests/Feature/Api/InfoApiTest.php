<?php

use App\List\LastScan;
use App\Models\AtsCompany;
use App\Models\ExchangeRate;
use App\Models\SentNotification;
use App\Models\SourceRun;
use App\Scan\NotificationOutcome;
use App\Scan\ScanFinished;
use App\Scan\ScanResult;
use App\Sources\Ats\Ats;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\Feature\Api\ApiHelpers;

uses(RefreshDatabase::class);

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-11-02T10:00:00Z');
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

it('gives the country table, sorted by name', function () {
    $countries = $this->getJson('/api/countries')->assertOk()->json();

    expect($countries)->toContain(['code' => 'IT', 'name' => 'Italy'])
        ->and($countries)->toContain(['code' => 'AE', 'name' => 'United Arab Emirates'])
        ->and(array_column($countries, 'name'))->toBe(collect($countries)->pluck('name')->sort(SORT_NATURAL | SORT_FLAG_CASE)->values()->all())
        ->and(count($countries))->toBeGreaterThan(200);
});

it('gives the rate date and the name of the rate source', function () {
    $this->getJson('/api/rates')->assertOk()->assertExactJson(['date' => null, 'source' => 'European Central Bank']);

    ExchangeRate::query()->create(['currency' => 'USD', 'rate' => 1.08, 'rate_date' => '2026-10-31', 'fetched_at' => CarbonImmutable::now()]);

    $this->getJson('/api/rates')->assertOk()->assertExactJson(['date' => '2026-10-31', 'source' => 'European Central Bank']);
});

it('gives the status before the first scan', function () {
    $this->getJson('/api/status')->assertOk()->assertExactJson([
        'lastScan' => ['startedAt' => null, 'finishedAt' => null],
        'nextScanAt' => null,
        'lastNotification' => null,
    ]);
});

it('gives the last scan, the next scan 5 minutes after its start and the last notification', function () {
    event(new ScanFinished(new ScanResult([], [], 0, NotificationOutcome::NoMatches), CarbonImmutable::parse('2026-11-02T09:55:00Z'), CarbonImmutable::parse('2026-11-02T09:55:20Z')));
    SentNotification::query()->create(['sent_at' => '2026-11-02T08:00:00Z', 'job_count' => 3, 'text' => '3 new jobs']);
    SentNotification::query()->create(['sent_at' => '2026-11-02T09:00:00Z', 'job_count' => 6, 'text' => '6 new jobs: 4 Laravel, 2 PHP']);

    $this->getJson('/api/status')->assertOk()->assertExactJson([
        'lastScan' => ['startedAt' => '2026-11-02T09:55:00Z', 'finishedAt' => '2026-11-02T09:55:20Z'],
        'nextScanAt' => '2026-11-02T10:00:00Z',
        'lastNotification' => ['sentAt' => '2026-11-02T09:00:00Z', 'text' => '6 new jobs: 4 Laravel, 2 PHP', 'jobCount' => 6],
    ]);

    expect(app(LastScan::class)->toArray()['nextAt'])->toBe('2026-11-02T10:00:00Z');
});

it('gives the diagnostic report with the versions and the status of each source', function () {
    Process::fake(['sw_vers -productVersion' => Process::result("15.1\n")]);
    config(['nativephp.version' => '1.2.3']);
    ApiHelpers::settings(['disabledSources' => ['jobicy']]);
    SourceRun::query()->create(['source' => 'larajobs', 'started_at' => '2026-11-02T08:00:00Z', 'finished_at' => '2026-11-02T08:00:02Z', 'error' => 'cURL error 60 for https://larajobs.com/feed', 'error_kind' => 'certificate']);

    $report = $this->getJson('/api/diagnostics')->assertOk()->json();

    expect(array_keys($report))->toBe(['appVersion', 'macosVersion', 'phpVersion', 'sources'])
        ->and($report['appVersion'])->toBe('1.2.3')
        ->and($report['macosVersion'])->toBe('15.1')
        ->and($report['phpVersion'])->toBe(PHP_VERSION)
        ->and(collect($report['sources'])->keyBy('key')['larajobs'])->toBe([
            'key' => 'larajobs',
            'enabled' => true,
            'lastRunAt' => '2026-11-02T08:00:00Z',
            'lastSuccessAt' => null,
            'errorKind' => 'certificate',
        ])
        ->and(collect($report['sources'])->keyBy('key')['jobicy']['enabled'])->toBeFalse();
});

it('gives null for the macOS version when sw_vers fails', function () {
    Process::fake(['sw_vers -productVersion' => Process::result('', 'not found', 127)]);

    $this->getJson('/api/diagnostics')->assertOk()->assertJsonPath('macosVersion', null);
});

it('has no settings, keywords, companies or jobs in the diagnostic report', function () {
    Process::fake(['sw_vers -productVersion' => Process::result("15.1\n")]);
    ApiHelpers::settings(['keywordsInclude' => ['secretkeyword'], 'companyBlocklist' => ['Blockedco'], 'timezone' => 'Asia/Tokyo']);
    AtsCompany::query()->create(['ats' => Ats::Greenhouse, 'slug' => 'privateco', 'display_name' => 'Privateco']);
    ApiHelpers::job('1', ['title' => 'Unique Title For Diagnostics', 'company' => 'Jobcompany']);

    $body = $this->getJson('/api/diagnostics')->assertOk()->getContent();

    foreach (['secretkeyword', 'Blockedco', 'Asia', 'Tokyo', 'IT', 'privateco', 'Privateco', 'Unique Title', 'Jobcompany', 'example.com', 'country', 'keywords'] as $private) {
        expect($body)->not->toContain($private);
    }
});
