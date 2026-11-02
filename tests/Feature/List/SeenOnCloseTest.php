<?php

use App\List\JobState;
use App\Listeners\MarkListSeenOnClose;
use App\Listeners\RememberListOpened;
use App\Models\Job;
use App\Models\StoredSettings;
use App\Schema\Eligibility;
use App\Schema\NormalisedJob;
use App\Schema\WorkMode;
use App\Storage\JobStore;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Native\Desktop\Events\MenuBar\MenuBarHidden;
use Native\Desktop\Events\MenuBar\MenuBarShown;
use Tests\Unit\Filters\Make;

uses(RefreshDatabase::class);

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-11-02T10:00:00Z');
    StoredSettings::query()->create(['data' => Make::settings(['country' => 'IT'])->toArray()]);
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

function seenJob(string $id, array $overrides = []): Job
{
    app(JobStore::class)->save([new NormalisedJob(...jobAttributes(array_merge([
        'sourceJobId' => $id,
        'title' => "Laravel Developer {$id}",
        'eligibility' => Eligibility::worldwide(),
        'postedAt' => CarbonImmutable::now(),
        'fetchedAt' => CarbonImmutable::now(),
    ], $overrides)))]);

    return Job::query()->where('source_job_id', $id)->sole();
}

it('listens for the list to open and to close', function () {
    Event::fake();

    Event::assertListening(MenuBarShown::class, RememberListOpened::class);
    Event::assertListening(MenuBarHidden::class, MarkListSeenOnClose::class);
});

it('marks the New matches that existed when the list opened as seen when it closes', function () {
    $before = seenJob('1');
    event(new MenuBarShown);

    CarbonImmutable::setTestNow('2026-11-02T10:01:00Z');
    $during = seenJob('2');
    CarbonImmutable::setTestNow('2026-11-02T10:02:00Z');
    event(new MenuBarHidden);

    expect($before->fresh()?->seen_at?->toIso8601ZuluString())->toBe('2026-11-02T10:02:00Z')
        ->and($during->fresh()?->seen_at)->toBeNull();
});

it('marks only the New matches', function () {
    $group = seenJob('1', ['eligibility' => Eligibility::unknown()]);
    $fails = seenJob('2', ['workMode' => WorkMode::Onsite]);
    $saved = seenJob('3');
    Job::query()->whereKey($saved->id)->update(['state' => JobState::Saved]);

    event(new MenuBarShown);
    event(new MenuBarHidden);

    expect($group->fresh()?->seen_at)->toBeNull()
        ->and($fails->fresh()?->seen_at)->toBeNull()
        ->and($saved->fresh()?->seen_at)->toBeNull();
});

it('keeps the first seen time', function () {
    $job = seenJob('1');
    Job::query()->whereKey($job->id)->update(['seen_at' => CarbonImmutable::parse('2026-11-02T09:00:00Z')]);

    event(new MenuBarShown);
    event(new MenuBarHidden);

    expect($job->fresh()?->seen_at?->toIso8601ZuluString())->toBe('2026-11-02T09:00:00Z');
});

it('marks nothing when the list closes with no open time', function () {
    $job = seenJob('1');

    event(new MenuBarHidden);

    expect($job->fresh()?->seen_at)->toBeNull();
});

it('uses the open time only once', function () {
    event(new MenuBarShown);
    event(new MenuBarHidden);
    $job = seenJob('1');

    event(new MenuBarHidden);

    expect($job->fresh()?->seen_at)->toBeNull();
});
