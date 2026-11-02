<?php

use App\List\UnseenBadge;
use App\Listeners\UpdateBadgeAfterScan;
use App\Models\Job;
use App\Models\StoredSettings;
use App\Scan\Notifier;
use App\Scan\Scan;
use App\Scan\ScanFinished;
use App\Schema\Eligibility;
use App\Schema\NormalisedJob;
use App\Sources\SourceRegistry;
use App\Storage\JobStore;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Native\Desktop\Events\MenuBar\MenuBarHidden;
use Native\Desktop\Events\MenuBar\MenuBarShown;
use Tests\Fakes\FakeNotifier;
use Tests\Fakes\FakeSource;
use Tests\Support\JobFields;
use Tests\Unit\Filters\Make;

uses(RefreshDatabase::class);

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-11-02T10:00:00Z');
    StoredSettings::query()->create(['data' => Make::settings(['country' => 'IT', 'postedWithinHours' => 720])->toArray()]);
    Http::fake([
        '*/menu-bar/label' => Http::response(),
        'www.ecb.europa.eu/*' => Http::response(JobFields::fixture('ecb/eurofxref-daily.xml')),
    ]);
    config(['nativephp-internal.running' => true]);
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

function badgeJob(string $id): void
{
    app(JobStore::class)->save([new NormalisedJob(...jobAttributes([
        'sourceJobId' => $id,
        'title' => "Laravel Developer {$id}",
        'eligibility' => Eligibility::worldwide(),
        'postedAt' => CarbonImmutable::now(),
        'fetchedAt' => CarbonImmutable::now(),
    ]))]);
}

/**
 * @return list<string> the labels sent to the internal API, in order
 */
function sentLabels(): array
{
    return Http::recorded(fn (Request $request) => str_ends_with($request->url(), 'menu-bar/label'))
        ->map(fn (array $pair) => $pair[0]['label'])
        ->values()
        ->all();
}

it('sets the menu bar label to the unseen count', function () {
    badgeJob('1');
    badgeJob('2');

    app(UnseenBadge::class)->refresh();

    expect(sentLabels())->toBe(['2']);
});

it('sets an empty label when there is nothing unseen', function () {
    app(UnseenBadge::class)->refresh();

    expect(sentLabels())->toBe(['']);
});

it('does not call the internal API outside the app', function () {
    config(['nativephp-internal.running' => false]);
    badgeJob('1');

    app(UnseenBadge::class)->refresh();

    Http::assertNothingSent();
});

it('does not throw when the internal API fails', function () {
    Http::fake(['*/menu-bar/label' => Http::failedConnection('Connection refused')]);

    app(UnseenBadge::class)->refresh();

    expect(true)->toBeTrue();
});

it('listens for the end of a scan', function () {
    Event::fake();

    Event::assertListening(ScanFinished::class, UpdateBadgeAfterScan::class);
});

it('updates the label after each scan', function () {
    app()->instance(Notifier::class, new FakeNotifier);
    // FakeSource stores two jobs that match.
    app()->instance(SourceRegistry::class, new SourceRegistry([new FakeSource]));

    app(Scan::class)->run();

    expect(sentLabels())->toBe(['2']);
});

it('updates the label after the list closes', function () {
    badgeJob('1');

    event(new MenuBarShown);
    event(new MenuBarHidden);

    expect(sentLabels())->toBe(['']);
});

it('keeps the count of a job stored while the list was open', function () {
    badgeJob('1');
    event(new MenuBarShown);
    CarbonImmutable::setTestNow('2026-11-02T10:01:00Z');
    badgeJob('2');

    event(new MenuBarHidden);

    expect(sentLabels())->toBe(['1'])
        ->and(Job::query()->whereNull('seen_at')->pluck('source_job_id')->all())->toBe(['2']);
});
