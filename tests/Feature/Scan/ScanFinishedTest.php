<?php

use App\Models\StoredSettings;
use App\Scan\Notifier;
use App\Scan\Scan;
use App\Scan\ScanFinished;
use App\Sources\SourceRegistry;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\Fakes\FakeNotifier;
use Tests\Support\JobFields;
use Tests\Unit\Filters\Make;

uses(RefreshDatabase::class);

beforeEach(function () {
    CarbonImmutable::setTestNow('2026-11-02T10:00:00Z');
    app()->instance(Notifier::class, new FakeNotifier);
    app()->instance(SourceRegistry::class, new SourceRegistry([]));
    StoredSettings::query()->create(['data' => Make::settings(['country' => 'IT'])->toArray()]);
    Http::fake(['www.ecb.europa.eu/*' => Http::response(JobFields::fixture('ecb/eurofxref-daily.xml'))]);
});

afterEach(function () {
    CarbonImmutable::setTestNow();
});

it('dispatches ScanFinished with the result and the start and end times of the scan', function () {
    Event::fake([ScanFinished::class]);

    $result = app(Scan::class)->run();

    Event::assertDispatchedTimes(ScanFinished::class, 1);
    Event::assertDispatched(ScanFinished::class, fn (ScanFinished $event) => $event->result === $result
        && $event->startedAt->toIso8601ZuluString() === '2026-11-02T10:00:00Z'
        && $event->finishedAt->toIso8601ZuluString() === '2026-11-02T10:00:00Z');
});

it('dispatches ScanFinished also when the scan sends no notification', function () {
    Event::fake([ScanFinished::class]);

    app(Scan::class)->run(notify: false);

    Event::assertDispatchedTimes(ScanFinished::class, 1);
});
